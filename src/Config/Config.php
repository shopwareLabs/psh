<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

/**
 * Represents the global configuration consisting of multiple environments
 */
class Config
{
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
     * @var array
     */
    private $params;

    /**
     * @param string|null $header
     * @param string $defaultEnvironment
     * @param ConfigEnvironment[] $environments
     * @param array $params
     */
    public function __construct(
        string $header = null,
        string $defaultEnvironment,
        array $environments,
        array $params
    ) {
        $this->header = $header;
        $this->defaultEnvironment = $defaultEnvironment;
        $this->environments = $environments;
        $this->params = $params;
    }

    /**
     * @return ScriptPath[]
     */
    public function getAllScriptPaths(): array
    {
        $paths = [];

        foreach ($this->environments as $name => $environmentConfig) {
            foreach ($environmentConfig->getAllScriptPaths() as $path) {
                if ($name !== $this->defaultEnvironment) {
                    $paths[] = new ScriptPath($path, $name);
                } else {
                    $paths[] = new ScriptPath($path);
                }
            }
        }

        return $paths;
    }

    /**
     * @param string|null $environment
     * @return array
     */
    public function getTemplates(string $environment = null): array
    {
        return $this->createResult(
            [$this->getEnvironment(), 'getTemplates'],
            [$this->getEnvironment($environment), 'getTemplates']
        );
    }

    /**
     * @param string|null $environment
     * @return array
     */
    public function getDynamicVariables(string $environment = null): array
    {
        return $this->createResult(
            [$this->getEnvironment(), 'getDynamicVariables'],
            [$this->getEnvironment($environment), 'getDynamicVariables']
        );
    }

    /**
     * @param string|null $environment
     * @return array
     */
    public function getConstants(string $environment = null): array
    {
        return $this->createResult(
            [$this->getEnvironment(), 'getConstants'],
            [$this->getEnvironment($environment), 'getConstants'],
            [$this, 'getParams']
        );
    }

    /**
     * @param string|null $environment
     *
     * @return ScriptPath[]
     */
    public function getDotenvPaths(string $environment = null): array
    {
        $paths = $this->createResult(
            [$this->getEnvironment(), 'getDotenvPaths'],
            [$this->getEnvironment($environment), 'getDotenvPaths']
        );

        return array_map(function (string $path): ScriptPath {
            return new ScriptPath($path, null);
        }, $paths);
    }

    /**
     * @return string
     */
    public function getHeader()
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

    /**
     * @return string
     */
    public function getDefaultEnvironment(): string
    {
        return $this->defaultEnvironment;
    }

    /**
     * @return array
     */
    public function getParams() : array
    {
        return $this->params;
    }

    /**
     * @param callable[] ...$valueProviders
     * @return array
     */
    private function createResult(callable ...$valueProviders): array
    {
        $mergedKeyValues = [];

        foreach ($valueProviders as $valueProvider) {
            foreach ($valueProvider() as $key => $value) {
                $mergedKeyValues[$key] = $value;
            }
        }

        return $mergedKeyValues;
    }

    /**
     * @param string|null $name
     * @return ConfigEnvironment
     */
    private function getEnvironment(string $name = null): ConfigEnvironment
    {
        if (!$name) {
            return $this->environments[$this->defaultEnvironment];
        }

        return $this->environments[$name];
    }
}
