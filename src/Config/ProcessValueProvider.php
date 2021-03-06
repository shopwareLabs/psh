<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Symfony\Component\Process\Process;
use function trim;

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

    public function getValue(): string
    {
        $this->process->mustRun();

        return trim($this->process->getOutput());
    }

    public function getCommand(): string
    {
        return $this->process->getCommandLine();
    }
}
