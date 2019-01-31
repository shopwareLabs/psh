<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

use Symfony\Component\Process\Process;

class DeferredProcess
{
    /**
     * @var string
     */
    private $parsedCommand;

    /**
     * @var ProcessCommand
     */
    private $command;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var LogMessage[]
     */
    private $log = [];

    /**
     * @param string $parsedCommand
     * @param ProcessCommand $command
     * @param Process $process
     */
    public function __construct(string $parsedCommand, ProcessCommand $command, Process $process)
    {
        $this->command = $command;
        $this->process = $process;
        $this->parsedCommand = $parsedCommand;
    }

    /**
     * @return string
     */
    public function getParsedCommand(): string
    {
        return $this->parsedCommand;
    }

    /**
     * @return ProcessCommand
     */
    public function getCommand(): ProcessCommand
    {
        return $this->command;
    }

    /**
     * @return Process
     */
    public function getProcess(): Process
    {
        return $this->process;
    }

    /**
     * @param LogMessage $logMessage
     */
    public function log(LogMessage $logMessage)
    {
        $this->log[] = $logMessage;
    }

    /**
     * @return LogMessage[]
     */
    public function getLog(): array
    {
        return $this->log;
    }
}
