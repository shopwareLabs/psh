<?php declare (strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use Symfony\Component\Process\Process;

/**
 * Enables lazy initialization of variables
 */
class ProcessValueProvider implements ValueProvider
{
    /**
     * @var Process
     */
    private $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * @return mixed
     */
    public function getValue(): string
    {
        $this->process->mustRun();

        return trim($this->process->getOutput());
    }
}
