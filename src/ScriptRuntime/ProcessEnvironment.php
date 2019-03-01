<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

use function array_merge;
use function dirname;
use Dotenv\Dotenv;
use Dotenv\Environment\DotenvFactory;
use function pathinfo;
use const PATHINFO_BASENAME;
use const PATHINFO_DIRNAME;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;
use Shopware\Psh\Config\ScriptPath;
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
     * @var array
     */
    private $dotenvVariables;

    /**
     * @param array $constants
     * @param array $variables
     * @param array $templates
     * @param array $dotenvPaths
     */
    public function __construct(array $constants, array $variables, array $templates, array $dotenvPaths)
    {
        $this->constants = $this->initializeConstants($constants);
        $this->variables = $this->initializeVariables($variables);
        $this->templates = $this->initializeTemplates($templates);
        $this->dotenvVariables = $this->initializeDotenvVariables($dotenvPaths);
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
     * @param ScriptPath[] $dotenvPaths
     *
     * @return array
     */
    private function initializeDotenvVariables(array $dotenvPaths): array
    {
        $variables = [];

        foreach ($dotenvPaths as $dotenvPath) {
            $dotenvVariables = $this->getDotenvVariables($dotenvPath);

            foreach ($dotenvVariables as $variableKey => $variableValue) {
                $variables[$variableKey] = new SimpleValueProvider($variableValue);
            }
        }

        return $variables;
    }

    /**
     * @param array $templates
     * @return ValueProvider[]
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
            $this->dotenvVariables,
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

    /**
     * @param ScriptPath $dotenvPath
     *
     * @return array
     */
    private function getDotenvVariables(ScriptPath $dotenvPath): array
    {
        $filePath = $dotenvPath->getPath();

        $dotenv = Dotenv::create(
            pathinfo($filePath, PATHINFO_DIRNAME),
            pathinfo($filePath, PATHINFO_BASENAME),
            new DotenvFactory()
        );

        return $dotenv->load();
    }
}
