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

        foreach ($commands as $index => $command) {
            switch ($command) {
                case $command instanceof ProcessCommand:
                    $parsedCommand = $this->getParsedShellCommand($command);

                    $this->logger->logCommandStart(
                        $parsedCommand,
                        $command->getLineNumber(),
                        $command->isIgnoreError(),
                        $index,
                        count($commands)
                    );

                    $process = $this->environment->createProcess($parsedCommand);

                    $this->setUpProcess($command, $process);
                    $this->runProcess($process);
                    $this->testProcessResultValid($command, $process);
                    break;
                case $command instanceof TemplateCommand:
                    $template = $command->getTemplate();

                    $this->logger->logTemplate(
                        $template->getDestination(),
                        $command->getLineNumber(),
                        $index,
                        count($commands)
                    );

                    $this->renderTemplate($template);
                break;
            }
        }

        $this->logger->finishScript($script);
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
    protected function setUpProcess(ProcessCommand $command, Process $process)
    {
        $process->setWorkingDirectory($this->applicationDirectory);
        $process->setTimeout(0);
        $process->setTty($command->isTTy());
    }

    /**
     * @param Process $process
     */
    protected function runProcess(Process $process)
    {
        $process->run(function ($type, $response) {
            if (Process::ERR === $type) {
                $this->logger->err($response);
            } else {
                $this->logger->out($response);
            }
        });
    }

    /**
     * @param ProcessCommand $command
     * @param Process $process
     */
    protected function testProcessResultValid(ProcessCommand $command, Process $process)
    {
        if (!$command->isIgnoreError() && !$process->isSuccessful()) {
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
}
