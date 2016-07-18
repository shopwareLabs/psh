<?php declare (strict_types = 1);


namespace Shopware\Psh\ScriptRuntime;

use Shopware\Psh\Listing\Script;
use Symfony\Component\Process\Process;

class ProcessExecutor
{

    /**
     * @var Environment
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
     * @param Environment $environment
     * @param TemplateEngine $templateEngine
     * @param Logger $logger
     * @param string $applicationDirectory
     */
    public function __construct(Environment $environment, TemplateEngine $templateEngine, Logger $logger, string $applicationDirectory)
    {
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
        $this->logger->logScript($script);

        foreach ($commands as $index => $command) {
            $parsedCommand = $this->getParsedShellCommand($command);

            $this->logger->logCommandStart($parsedCommand, $command->getLineNumber(), $command->isIgnoreError(), $index, count($commands));

            $process = $this->environment->createProcess($parsedCommand);

            $this->setUpProcess($command, $process);
            $this->runProcess($process);
            $this->validateProcessResult($command, $process);
        }
    }

    /**
     * @param Command $command
     * @return string
     */
    protected function getParsedShellCommand(Command $command): string
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
    protected function setUpProcess(Command $command, Process $process)
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
     * @param Command $command
     * @param Process $process
     */
    protected function validateProcessResult(Command $command, Process $process)
    {
        if (!$command->isIgnoreError() && !$process->isSuccessful()) {
            throw new ExecutionErrorException('Command exited with Error');
        }
    }
}
