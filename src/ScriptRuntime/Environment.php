<?php declare(strict_types = 1);

namespace Shopware\Psh\ScriptRuntime;

use Symfony\Component\Process\Process;

class Environment
{
    /**
     * @var array
     */
    private $constants;
    /**
     * @var array
     */
    private $variables;

    public function __construct(array $constants, array $variables)
    {
        $this->constants = $constants;
        $this->variables = $this->initializeVariables($variables);
    }

    private function initializeVariables(array $variables): array
    {
        $resolvedVariables = [];
        foreach($variables as $name => $shellCommand) {
            $process = $this->createProcess($shellCommand);
            $process->mustRun();

            $resolvedVariables[$name] = trim($process->getOutput());
        }

        return $resolvedVariables;
    }


    public function getAllValues(): array
    {
        return array_merge(
            $this->constants,
            $this->variables
        );
    }

    public function createProcess(string $shellCommand): Process
    {
        return new Process($shellCommand);
    }
}