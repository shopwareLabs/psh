<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

use Symfony\Component\Process\Process;

/**
 * Create representation of the current environment variables and constants
 */
class ProcessEnvironment
{
    /**
     * @var ValueProvider[]
     */
    private $constants;
    /**
     * @var ValueProvider[]
     */
    private $variables;
    /**
     * @var Template[]
     */
    private $templates;

    /**
     * @param array $constants
     * @param array $variables
     */
    public function __construct(array $constants, array $variables, array $templates)
    {
        $this->constants = $this->initializeConstants($constants);
        $this->variables = $this->initializeVariables($variables);
        $this->templates = $this->initializeTemplates($templates);
    }

    /**
     * @param array $constants
     * @return ValueProvider[]
     */
    private function initializeConstants(array $constants): array
    {
        $resolvedValues = [];
        foreach ($constants as $name => $value) {
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
        foreach ($variables as $name => $shellCommand) {
            $process = $this->createProcess($shellCommand);
            $resolvedVariables[$name] = new ProcessValueProvider($process);
        }

        return $resolvedVariables;
    }

    /**
     * @param array $variables
     * @return ValueProvider[]
     */
    private function initializeTemplates(array $variables): array
    {
        $resolvedVariables = [];
        foreach ($variables as $template) {
            $resolvedVariables[] = new Template($template['source'], $template['destination']);
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

    /**
     * @return Template[]
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @param string $shellCommand
     * @return Process
     */
    public function createProcess(string $shellCommand): Process
    {
        return new Process($shellCommand);
    }
}
