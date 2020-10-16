<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use Shopware\Psh\ScriptRuntime\DeferredProcessCommand;
use Symfony\Component\Process\Process;

class DeferredProcess
{
    /**
     * @var string
     */
    private $parsedCommand;

    /**
     * @var DeferredProcessCommand
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

    public function __construct(string $parsedCommand, DeferredProcessCommand $command, Process $process)
    {
        $this->command = $command;
        $this->process = $process;
        $this->parsedCommand = $parsedCommand;
    }

    public function getParsedCommand(): string
    {
        return $this->parsedCommand;
    }

    public function getCommand(): DeferredProcessCommand
    {
        return $this->command;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function log(LogMessage $logMessage): void
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
