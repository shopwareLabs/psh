<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\MultiReader;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Symfony\Component\Process\Process;
use function array_keys;
use function pathinfo;

class EnvironmentResolver
{
    /**
     * @param DotenvFile[] $dotenvPaths
     * @return ValueProvider[]
     */
    public function resolveDotenvVariables(array $dotenvPaths): array
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

    /**
     * @return ValueProvider[]
     */
    public function resolveConstants(array $constants): array
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
    public function resolveVariables(array $variables): array
    {
        $resolvedVariables = [];
        foreach ($variables as $name => $shellCommand) {
            $process = $this->createProcess($shellCommand);
            $resolvedVariables[$name] = new ProcessValueProvider($process);
        }

        return $resolvedVariables;
    }

    /**
     * @return Template[]
     */
    public function resolveTemplates(array $templates): array
    {
        $resolvedVariables = [];
        foreach ($templates as $template) {
            $resolvedVariables[] = new Template($template['source'], $template['destination']);
        }

        return $resolvedVariables;
    }

    private function createProcess(string $shellCommand): Process
    {
        return new Process($shellCommand);
    }
}
