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
     * @param array $constantDefinitions
     * @param array $variableDefinitions
     * @param array $templateDefinitions
     */
    public function __construct(array $constantDefinitions, array $variableDefinitions, array $templateDefinitions)
    {
        $this->constants = $this->initializeConstants($constantDefinitions);
        $this->variables = $this->initializeVariables($variableDefinitions);
        $this->templates = $this->initializeTemplates($templateDefinitions);
    }

    /**
     * @param array $constants
     * @return ValueProvider[]
     */
    private function initializeConstants(array $constants): array
    {
        $resolvedValues = [];
        foreach ($constants as $name => $value) {
            $resolvedValues[$name] = new SimpleValueProvider((string) $value);
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
     * @param array $templates
     * @return Template[]
     */
    private function initializeTemplates(array $templates): array
    {
        $resolvedVariables = [];
        foreach ($templates as $template) {
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
