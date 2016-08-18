<?php declare (strict_types = 1);

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
     * @param string|null $header
     * @param string $defaultEnvironment
     * @param ConfigEnvironment[] $environments
     */
    public function __construct(
        string $header = null,
        string $defaultEnvironment,
        array $environments
    ) {
        $this->header = $header;
        $this->defaultEnvironment = $defaultEnvironment;
        $this->environments = $environments;
    }

    /**
     * @return array
     */
    public function getAllScriptPaths(): array
    {
        $paths = [];

        foreach ($this->environments as $name => $environmentConfig) {
            foreach ($environmentConfig->getAllScriptPaths() as $path) {
                if ($name !== $this->defaultEnvironment) {
                    $paths[$name] = $path;
                } else {
                    $paths[] = $path;
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
            [$this->getEnvironment($environment), 'getConstants']
        );
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    private function createResult(callable $defaultValues, callable $specificValues): array
    {
        $mergedKeyValues = [];

        foreach ($defaultValues() as $key => $value) {
            $mergedKeyValues[$key] = $value;
        }

        foreach ($specificValues() as $key => $value) {
            $mergedKeyValues[$key] = $value;
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
