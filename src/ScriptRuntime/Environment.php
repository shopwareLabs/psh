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

    /**
     * @param array $constants
     * @param array $variables
     */
    public function __construct(array $constants, array $variables)
    {
        $this->constants = $this->initializeConstants($constants);
        $this->variables = $this->initializeVariables($variables);
    }

    /**
     * @param array $constants
     * @return ValueProvider[]
     */
    private function initializeConstants(array $constants): array
    {
        $resolvedValues = [];
        foreach($constants as $name => $value) {
            $resolvedValues[$name] = new SimpleValueProvider($value);
        }
        return $resolvedValues;
    }

    /**
     * @param array $variables
     * @return ValueProvider[]
     */
    private function initializeVariables(array $variables): array
    {
        $resolvedVariables = [];
        foreach($variables as $name => $shellCommand) {
            $process = $this->createProcess($shellCommand);
            $resolvedVariables[$name] = new ProcessValueProvider($process);
        }

        return $resolvedVariables;
    }


    /**
     * @return ValueProvider[]
     */
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