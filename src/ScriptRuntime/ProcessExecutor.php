<?php declare(strict_types=1);


namespace Shopware\Psh\ScriptRuntime;

use Shopware\Psh\Listing\Script;
use Symfony\Component\Process\Process;

/**
 * Execute a command in a separate process
 */
class ProcessExecutor
{
    /**
     * @var ProcessEnvironment
     */
    private $environment;

    /**
     * @var TemplateEngine
     */
    private $templateEngine;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $applicationDirectory;

    /**
     * @var DeferredProcess[]
     */
    private $deferredProcesses = [];

    /**
     * ProcessExecutor constructor.
     * @param ProcessEnvironment $environment
     * @param TemplateEngine $templateEngine
     * @param Logger $logger
     * @param string $applicationDirectory
     */
    public function __construct(
        ProcessEnvironment $environment,
        TemplateEngine $templateEngine,
        Logger $logger,
        string $applicationDirectory
    ) {
        $this->environment = $environment;
        $this->templateEngine = $templateEngine;
        $this->logger = $logger;
        $this->applicationDirectory = $applicationDirectory;
    }

    /**
     * @param Script $script
     * @param Command[] $commands
     */
    public function execute(Script $script, array $commands)
    {
        $this->logger->startScript($script);

        $this->executeTemplateRendering();

        try {
            foreach ($commands as $index => $command) {
                $this->executeCommand($command, $index, count($commands));
            }
        } finally {
            $this->waitForDeferredProcesses();
        }

        $this->logger->finishScript($script);
    }

    /**
     * @param Command $command
     * @param int $index
     * @param int $totalCount
     */
    private function executeCommand(Command $command, int $index, int $totalCount)
    {
        switch ($command) {
            case $command instanceof ProcessCommand:
                $parsedCommand = $this->getParsedShellCommand($command);
                $process = $this->environment->createProcess($parsedCommand);
                $this->setProcessDefaults($command, $process);

                if ($command->isDeferred()) {
                    $this->logger->logStart(
                        'Deferring',
                        $parsedCommand,
                        $command->getLineNumber(),
                        $command->isIgnoreError(),
                        $index,
                        $totalCount
                    );
                    $this->deferProcess($parsedCommand, $command, $process);
                } else {
                    $this->logger->logStart(
                        'Starting',
                        $parsedCommand,
                        $command->getLineNumber(),
                        $command->isIgnoreError(),
                        $index,
                        $totalCount
                    );

                    $this->runProcess($process);
                    $this->testProcessResultValid($command, $process);
                }

                break;


            case $command instanceof TemplateCommand:
                $template = $command->getTemplate();

                $this->logger->logStart(
                    'Template',
                    $template->getDestination(),
                    $command->getLineNumber(),
                    false,
                    $index,
                    $totalCount
                );

                $this->renderTemplate($template);
                break;

            case $command instanceof WaitCommand:
                $this->logger->logStart(
                    'Waiting',
                    '',
                    $command->getLineNumber(),
                    false,
                    $index,
                    $totalCount
                );

                $this->waitForDeferredProcesses();
                break;
        }
    }

    private function executeTemplateRendering()
    {
        foreach ($this->environment->getTemplates() as $template) {
            $this->renderTemplate($template);
        }
    }

    /**
     * @param ProcessCommand $command
     * @return string
     */
    protected function getParsedShellCommand(ProcessCommand $command): string
    {
        $rawShellCommand = $command->getShellCommand();

        $parsedCommand = $this->templateEngine->render(
            $rawShellCommand,
            $this->environment->getAllValues()
        );

        return $parsedCommand;
    }

    /**
     * @param Process $process
     */
    private function setProcessDefaults(ProcessCommand $command, Process $process)
    {
        $process->setWorkingDirectory($this->applicationDirectory);
        $process->setTimeout(0);
        $process->setTty($command->isTTy());
    }

    /**
     * @param Process $process
     */
    private function runProcess(Process $process)
    {
        $process->run(function ($type, $response) {
            $this->logger->log(new LogMessage($response, $type === Process::ERR));
        });
    }

    /**
     * @param ProcessCommand $command
     * @param Process $process
     */
    protected function testProcessResultValid(ProcessCommand $command, Process $process)
    {
        if (!$this->isProcessResultValid($command, $process)) {
            throw new ExecutionErrorException('Command exited with Error');
        }
    }

    /**
     * @param $template
     */
    private function renderTemplate(Template $template)
    {
        $renderedTemplateDestination = $this->templateEngine
            ->render($template->getDestination(), $this->environment->getAllValues());

        $template->setDestination($renderedTemplateDestination);

        $renderedTemplateContent = $this->templateEngine
            ->render($template->getContent(), $this->environment->getAllValues());

        $template->setContents($renderedTemplateContent);
    }

    private function waitForDeferredProcesses()
    {
        if (!$this->deferredProcesses) {
            return;
        }

        $this->logger->logWait();

        foreach ($this->deferredProcesses as $index => $deferredProcess) {
            $deferredProcess->getProcess()->wait();

            $this->logger->logStart(
                'Output from',
                $deferredProcess->getParsedCommand(),
                $deferredProcess->getCommand()->getLineNumber(),
                $deferredProcess->getCommand()->isIgnoreError(),
                $index,
                count($this->deferredProcesses)
            );

            foreach ($deferredProcess->getLog() as $logMessage) {
                $this->logger->log($logMessage);
            }

            if ($this->isProcessResultValid($deferredProcess->getCommand(), $deferredProcess->getProcess())) {
                $this->logger->logSuccess();
            } else {
                $this->logger->logFailure();
            }
        }

        foreach ($this->deferredProcesses as $deferredProcess) {
            $this->testProcessResultValid($deferredProcess->getCommand(), $deferredProcess->getProcess());
        }

        $this->deferredProcesses = [];
    }

    /**
     * @param ProcessCommand $command
     * @param Process $process
     */
    private function deferProcess(string $parsedCommand, ProcessCommand $command, Process $process)
    {
        $deferredProcess = new DeferredProcess($parsedCommand, $command, $process);

        $process->start(function ($type, $response) use ($deferredProcess) {
            $deferredProcess->log(new LogMessage($response, $type === Process::ERR));
        });

        $this->deferredProcesses[] = $deferredProcess;
    }

    /**
     * @param ProcessCommand $command
     * @param Process $process
     * @return bool
     */
    protected function isProcessResultValid(ProcessCommand $command, Process $process): bool
    {
        return $command->isIgnoreError() || $process->isSuccessful();
    }
}
