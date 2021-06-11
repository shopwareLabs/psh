<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Shopware\Psh\Application\RuntimeParameters;
use function array_map;
use function array_merge;
use function in_array;
use function mb_strtoupper;

/**
 * Represents the global configuration consisting of multiple environments
 */
class Config
{
    /**
     * @var EnvironmentResolver
     */
    private $resolver;

    /**
     * @var string
     */
    private $header;

    /**
     * @var string
     */
    private $defaultEnvironment;

    /**
     * @var ConfigEnvironment[]
     */
    private $environments;

    /**
     * @var RuntimeParameters
     */
    private $runtimeParameters;

    /**
     * @param ConfigEnvironment[] $environments
     */
    public function __construct(
        EnvironmentResolver $resolver,
        string $defaultEnvironment,
        array $environments,
        RuntimeParameters $runtimeParameters,
        ?string $header = null
    ) {
        $this->resolver = $resolver;
        $this->defaultEnvironment = $defaultEnvironment;
        $this->environments = $environments;
        $this->runtimeParameters = $runtimeParameters;
        $this->header = $header;
    }

    /**
     * @return ScriptsPath[]
     */
    public function getAllScriptsPaths(): array
    {
        $paths = [];

        foreach ($this->environments as $name => $environmentConfig) {
            foreach ($environmentConfig->getAllScriptsPaths() as $path) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function getTemplates(?string $environment = null): array
    {
        $templates = $this->getEnvironment()->getTemplates();

        if ($environment === null) {
            return $templates;
        }

        foreach ($this->getEnvironment($environment)->getTemplates() as $template) {
            $templates[] = $template;
        }

        return $templates;
    }

    public function getDynamicVariables(?string $environment = null): array
    {
        return $this->resolver->resolveVariables($this->createMergedUpperCaseResult(
            [$this->getEnvironment(), 'getDynamicVariables'],
            [$this->getEnvironment($environment), 'getDynamicVariables']
        ));
    }

    public function getConstants(?string $environment = null): array
    {
        return $this->resolver->resolveConstants($this->createMergedUpperCaseResult(
            [$this->getEnvironment(), 'getConstants'],
            [$this->getEnvironment($environment), 'getConstants'],
            [$this, 'getParams']
        ));
    }

    public function getAllPlaceholders(?string $environment = null): array
    {
        return array_merge(
            $this->getConstants($environment),
            $this->getDotenvVariables($environment),
            $this->getDynamicVariables($environment)
        );
    }

    /**
     * @return ValueProvider[]
     */
    public function getDotenvVariables(?string $environment = null): array
    {
        $paths = $this->getDotenvPaths($environment);

        return $this->resolver->resolveDotenvVariables($paths);
    }

    /**
     * @return RequiredValue[]
     */
    public function getRequiredVariables(?string $environment = null): array
    {
        $requiredValues = $this->createMergedUpperCaseResult(
            [$this->getEnvironment(), 'getRequiredVariables'],
            [$this->getEnvironment($environment), 'getRequiredVariables']
        );

        $result = [];
        foreach ($requiredValues as $name => $description) {
            $result[$name] = new RequiredValue($name, $description);
        }

        return $result;
    }

    /**
     * @return DotenvFile[]
     */
    public function getDotenvPaths(?string $environment = null): array
    {
        $paths = $this->createMergedResult(
            [$this->getEnvironment(), 'getDotenvPaths'],
            [$this->getEnvironment($environment), 'getDotenvPaths']
        );

        return array_map(static function (string $path): DotenvFile {
            return new DotenvFile($path);
        }, $paths);
    }

    /**
     * @return string
     */
    public function getHeader(): ?string
    {
        return $this->header;
    }

    /**
     * @return ConfigEnvironment[]
     */
    public function getEnvironments(): array
    {
        return $this->environments;
    }

    public function getDefaultEnvironment(): string
    {
        return $this->defaultEnvironment;
    }

    public function hasOption(string $name): bool
    {
        return in_array($name, $this->runtimeParameters->getAppParams(), true);
    }

    public function getImports(): array
    {
        return $this->getEnvironment()->getImports();
    }

    public function getScriptNames(): array
    {
        return $this->runtimeParameters->getCommands();
    }

    private function getParams(): array
    {
        return $this->runtimeParameters->getOverwrites();
    }

    private function createMergedResult(callable ...$valueProviders): array
    {
        $mergedKeyValues = [];

        foreach ($valueProviders as $valueProvider) {
            foreach ($valueProvider() as $key => $value) {
                $mergedKeyValues[$key] = $value;
            }
        }

        return $mergedKeyValues;
    }

    private function createMergedUpperCaseResult(callable ...$valueProviders): array
    {
        $mergedKeyValues = [];

        foreach ($valueProviders as $valueProvider) {
            foreach ($valueProvider() as $key => $value) {
                $mergedKeyValues[mb_strtoupper($key)] = $value;
            }
        }

        return $mergedKeyValues;
    }

    private function getEnvironment(?string $name = null): ConfigEnvironment
    {
        if ($name === null) {
            return $this->environments[$this->defaultEnvironment];
        }

        return $this->environments[$name];
    }
}
