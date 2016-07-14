<?php declare(strict_types = 1);

namespace Shopware\Psh\ScriptRuntime;

use Symfony\Component\Process\Process;

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