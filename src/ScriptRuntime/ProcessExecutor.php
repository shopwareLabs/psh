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
    public function __construct(ProcessEnvironment $environment, TemplateEngine $templateEngine, Logger $logger, string $applicationDirectory)
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
        $this->logger->startScript($script);

        foreach ($this->environment->getTemplates() as $template) {
            $renderedTemplate = $this->templateEngine->render($template->getContent(), $this->environment->getAllValues());
            $template->setContents($renderedTemplate);
        }

        $extraEnvironment = [];
        foreach ($commands as $index => $command) {
            $parsedCommand = $this->getParsedShellCommand($command);

            $this->logger->logCommandStart($parsedCommand, $command->getLineNumber(), $command->isIgnoreError(), $index, count($commands));

            $process = $this->environment->createProcess($parsedCommand .  ' && echo "__PSH_ENV__" && printenv --null');

            $this->setUpProcess($command, $process, $extraEnvironment);
            $this->runProcess($process);
            $extraEnvironment = $this->extractEnvironmentData($process);
            $this->testProcessResultValid($command, $process);
        }

        $this->logger->finishScript($script);
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
     * @param Command $command
     * @param Process $process
     * @param array $shellEnvironment
     */
    protected function setUpProcess(Command $command, Process $process, array $shellEnvironment)
    {
        if ($shellEnvironment) {
            $process->setEnv($shellEnvironment);
        }
        
        $process->setWorkingDirectory($this->applicationDirectory);
        $process->setTimeout(0);
        $process->setTty($command->isTTy());
    }

    /**
     * @param Process $process
     * @return array the created environment
     */
    protected function runProcess(Process $process)
    {
        $pshEnvDetected = false;

        $process->run(function ($type, $response) use (&$pshEnvDetected) {
            if ($pshEnvDetected) {
                return;
            }

            $pshEnvStartIndex = strpos($response, '__PSH_ENV__');

            if (false !== $pshEnvStartIndex) {
                $pshEnvDetected = true;
                $response = substr($response, 0, $pshEnvStartIndex);
            }

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
    protected function testProcessResultValid(Command $command, Process $process)
    {
        if (!$command->isIgnoreError() && !$process->isSuccessful()) {
            throw new ExecutionErrorException('Command exited with Error');
        }
    }

    /**
     * @param Process $process
     * @return array
     */
    private function extractEnvironmentData(Process $process): array
    {
        $pshEnvStartIndex = strpos($process->getOutput(), '__PSH_ENV__');

        if (false === $pshEnvStartIndex) {
            return [];
        }

        $plainEnvironmentData = substr($process->getOutput(), $pshEnvStartIndex + strlen('__PSH_ENV__') + strlen(PHP_EOL));
        $rawKeyValuePairs = explode("\000", $plainEnvironmentData);

        $environment = [];
        foreach ($rawKeyValuePairs as $rawKeyValuePair) {
            if (0 === strlen(trim($rawKeyValuePair))) {
                continue;
            }

            $equalsSignPosition = strpos($rawKeyValuePair, '=');

            $key = substr($rawKeyValuePair, 0, $equalsSignPosition);
            $value = substr($rawKeyValuePair, $equalsSignPosition + 1);
            $environment[$key] = $value;
        }

        unset($environment['PWD']);

        return $environment;
    }
}
