<?php declare(strict_types=1);


namespace Shopware\Psh\ScriptRuntime\Execution;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\BashCommand;
use Shopware\Psh\ScriptRuntime\Command;
use Shopware\Psh\ScriptRuntime\DeferredProcessCommand;
use Shopware\Psh\ScriptRuntime\ProcessCommand;
use Shopware\Psh\ScriptRuntime\SynchronusProcessCommand;
use Shopware\Psh\ScriptRuntime\TemplateCommand;
use Shopware\Psh\ScriptRuntime\WaitCommand;
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
            case $command instanceof BashCommand:
                $originalContent = file_get_contents($command->getScript()->getPath());

                try {
                    file_put_contents($command->getScript()->getPath(), $this->templateEngine->render($originalContent, $this->environment->getAllValues()));

                    $process = $this->environment->createProcess($command->getScript()->getPath());
                    $this->setProcessDefaults($process, false);

                    $this->logger->logStart(
                        'Executing',
                        $command->getScript()->getPath(),
                        $command->getLineNumber(),
                        false,
                        $index,
                        $totalCount
                    );

                    $this->runProcess($process);
                    $this->testProcessResultValid($process, false);
                } finally {
                    file_put_contents($command->getScript()->getPath(), $originalContent);
                }


                break;
            case $command instanceof SynchronusProcessCommand:
                $parsedCommand = $this->getParsedShellCommand($command);
                $process = $this->environment->createProcess($parsedCommand);
                $this->setProcessDefaults($process, $command->isTTy());

                $this->logger->logStart(
                    'Starting',
                    $parsedCommand,
                    $command->getLineNumber(),
                    $command->isIgnoreError(),
                    $index,
                    $totalCount
                );

                $this->runProcess($process);
                $this->testProcessResultValid($process, $command->isIgnoreError());

                break;


            case $command instanceof DeferredProcessCommand:
                $parsedCommand = $this->getParsedShellCommand($command);
                $process = $this->environment->createProcess($parsedCommand);
                $this->setProcessDefaults($process, $command->isTTy());

                $this->logger->logStart(
                    'Defering',
                    $parsedCommand,
                    $command->getLineNumber(),
                    $command->isIgnoreError(),
                    $index,
                    $totalCount
                );
                $this->deferProcess($parsedCommand, $command, $process);

                break;


            case $command instanceof TemplateCommand:
                    $template = $command->createTemplate();

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
     * @param bool $isTty
     */
    private function setProcessDefaults(Process $process, bool $isTty)
    {
        $process->setWorkingDirectory($this->applicationDirectory);
        $process->setTimeout(0);
        $process->setTty($isTty);
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
     * @param Process $process
     * @param bool $ignoreError
     */
    protected function testProcessResultValid(Process $process, bool $ignoreError)
    {
        if (!$this->isProcessResultValid($process, $ignoreError)) {
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
        if (count($this->deferredProcesses) === 0) {
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

            if ($this->isProcessResultValid($deferredProcess->getProcess(), $deferredProcess->getCommand()->isIgnoreError())) {
                $this->logger->logSuccess();
            } else {
                $this->logger->logFailure();
            }
        }

        foreach ($this->deferredProcesses as $deferredProcess) {
            $this->testProcessResultValid($deferredProcess->getProcess(), $deferredProcess->getCommand()->isIgnoreError());
        }

        $this->deferredProcesses = [];
    }

    /**
     * @param string $parsedCommand
     * @param DeferredProcessCommand $command
     * @param Process $process
     */
    private function deferProcess(string $parsedCommand, DeferredProcessCommand $command, Process $process)
    {
        $deferredProcess = new DeferredProcess($parsedCommand, $command, $process);

        $process->start(function ($type, $response) use ($deferredProcess) {
            $deferredProcess->log(new LogMessage($response, $type === Process::ERR));
        });

        $this->deferredProcesses[] = $deferredProcess;
    }

    /**
     * @param Process $process
     * @param bool $ignoreError
     * @return bool
     */
    protected function isProcessResultValid(Process $process, bool $ignoreError): bool
    {
        return $ignoreError || $process->isSuccessful();
    }
}
