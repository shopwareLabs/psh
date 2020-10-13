<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\MultiReader;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Shopware\Psh\Config\DotenvFile;
use Symfony\Component\Process\Process;
use function array_keys;
use function array_merge;
use function pathinfo;

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
     * @param DotenvFile[] $dotenvPaths
     */
    public function __construct(array $constants, array $variables, array $templates, array $dotenvPaths)
    {
        $this->constants = $this->initializeConstants($constants);
        $this->variables = $this->initializeVariables($variables);
        $this->templates = $this->initializeTemplates($templates);
        $this->dotenvVariables = $this->initializeDotenvVariables($dotenvPaths);
    }

    /**
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
     * @param DotenvFile[] $dotenvPaths
     * @return ValueProvider[]
     */
    private function initializeDotenvVariables(array $dotenvPaths): array
    {
        $variables = [];

        foreach ($dotenvPaths as $dotenvPath) {
            $dotenvVariables = $this->loadDotenvVariables($dotenvPath);

            foreach ($dotenvVariables as $variableKey => $variableValue) {
                $variables[$variableKey] = new SimpleValueProvider($variableValue);
            }
        }

        return $variables;
    }

    /**
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

    public function createProcess(string $shellCommand): Process
    {
        return new Process($shellCommand);
    }

    private function loadDotenvVariables(DotenvFile $dotenvPath): array
    {
        $fileData = $this->loadEnvVarsFromDotenvFile($dotenvPath);

        return $this->diffDotenvVarsWithCurrentApplicationEnv($fileData);
    }

    private function loadEnvVarsFromDotenvFile(DotenvFile $dotenvPath): array
    {
        $filePath = $dotenvPath->getPath();

        $dotenv = Dotenv::createArrayBacked(
            pathinfo($filePath, \PATHINFO_DIRNAME),
            pathinfo($filePath, \PATHINFO_BASENAME)
        );

        return $dotenv->load();
    }

    private function diffDotenvVarsWithCurrentApplicationEnv(array $fileData): array
    {
        $fileKeys = array_keys($fileData);
        $result = [];

        $reader = new MultiReader([
            PutenvAdapter::create()->get(),
            ServerConstAdapter::create()->get(),
        ]);

        foreach ($fileKeys as $key) {
            $option = $reader->read($key);

            if ($option->isEmpty()) {
                $result[$key] = $fileData[$key];
                continue;
            }

            $result[$key] = $option->get();
        }

        return $result;
    }
}
