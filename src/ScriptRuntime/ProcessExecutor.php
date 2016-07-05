<?php declare(strict_types = 1);


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

    public function __construct(Environment $environment, TemplateEngine $templateEngine, Logger $logger)
    {
        $this->environment = $environment;
        $this->templateEngine = $templateEngine;
        $this->logger = $logger;
    }

    /**
     * @param Script $script
     * @param Command[] $commands
     */
    public function execute(Script $script, array $commands)
    {
        $this->logger->logScript($script);

        foreach($commands as $index =>  $command) {
            $rawShellCommand = $command->getShellCommand();

            $parsedCommand = $this->templateEngine->render(
                $rawShellCommand,
                $this->environment->getAllValues()
            );

            $this->logger->logCommandStart($parsedCommand, $command->getLineNumber(), $command->isIgnoreError(), $index, count($commands));

            $process = $this->environment->createProcess($parsedCommand);
            $process->setEnv($this->environment->getAllValues());
            $process->setWorkingDirectory($script->getDirectory());
            $process->setTimeout(0);

            $process->run(function($type, $response) {
                if (Process::ERR === $type) {
                    $this->logger->err($response);
                } else {
                    $this->logger->out($response);
                }
            });

            if(!$command->isIgnoreError() && !$process->isSuccessful()) {
                throw new ExecutionErrorException('Command exited with Error');
            }
        }
    }

}