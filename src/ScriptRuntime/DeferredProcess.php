<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

use Symfony\Component\Process\Process;

class DeferredProcess
{
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

    public function __construct(ProcessCommand $command, Process $process)
    {
        $this->command = $command;
        $this->process = $process;
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
