<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

/**
 * Builder pattern
 *
 * Create a config from a more complicated proto format by representing a stateful representation of config read so far.
 */
class ConfigBuilder
{
    const DEFAULT_ENV = '##default##';

    private $header = '';

    private $environments = [];

    private $currentEnvironment;

    private $currentCommandPaths;

    private $currentDotenvPaths;

    private $currentDynamicVariables;

    private $templates;

    private $currentConstants;

    private $hidden;

    /**
     * @param string|null $header
     * @return ConfigBuilder
     */
    public function setHeader(string $header = null): ConfigBuilder
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param string|null $environment
     * @return ConfigBuilder
     */
    public function start(string $environment = null): ConfigBuilder
    {
        $this->reset();
        if ($environment === null) {
            $environment = self::DEFAULT_ENV;
        }

        $this->currentEnvironment = $environment;
        return $this;
    }

    /**
     * @param bool $set
     * @return ConfigBuilder
     */
    public function setHidden(bool $set): ConfigBuilder
    {
        $this->hidden = $set;
        return $this;
    }

    /**
     * @param array $commandPaths
     * @return ConfigBuilder
     */
    public function setCommandPaths(array $commandPaths): ConfigBuilder
    {
        $this->currentCommandPaths = $commandPaths;
        return $this;
    }

    /**
     * @param array $dotenvPaths
     * @return ConfigBuilder
     */
    public function setDotenvPaths(array $dotenvPaths): ConfigBuilder
    {
        $this->currentDotenvPaths = [];

        foreach ($dotenvPaths as $dotenvPath) {
            $this->setDotenvPath($dotenvPath);
        }

        return $this;
    }

    /**
     * @param string $dotenvPath
     * @return ConfigBuilder
     */
    public function setDotenvPath(string $dotenvPath): ConfigBuilder
    {
        $this->currentDotenvPaths[pathinfo($dotenvPath, PATHINFO_BASENAME)] = $dotenvPath;

        return $this;
    }

    /**
     * @deprecated only used by yaml builder
     * @param array $dynamicVariables
     * @return ConfigBuilder
     */
    public function setDynamicVariables(array $dynamicVariables): ConfigBuilder
    {
        $this->currentDynamicVariables = $dynamicVariables;
        return $this;
    }

    public function setDynamicVariable(string $key, string $value): ConfigBuilder
    {
        $this->currentDynamicVariables[$key] = $value;
        return $this;
    }

    /**
     * @deprecated only used by yaml builder
     * @param array $constants
     * @return ConfigBuilder
     */
    public function setConstants(array $constants): ConfigBuilder
    {
        $this->currentConstants = $constants;
        return $this;
    }

    public function setConstVariable(string $key, string $value): ConfigBuilder
    {
        $this->currentConstants[$key] = $value;
        return $this;
    }

    /**
     * @param array $templates
     * @return ConfigBuilder
     */
    public function setTemplates(array $templates): ConfigBuilder
    {
        $this->templates = $templates;
        return $this;
    }

    /**
     * @return Config
     */
    public function create(array $params): Config
    {
        $this->reset();

        return new Config($this->header, self::DEFAULT_ENV, $this->environments, $params);
    }

    private function reset()
    {
        if ($this->currentEnvironment) {
            $this->environments[$this->currentEnvironment] = new ConfigEnvironment(
                $this->hidden,
                $this->currentCommandPaths,
                $this->currentDynamicVariables,
                $this->currentConstants,
                $this->templates,
                $this->currentDotenvPaths
            );
        }

        $this->currentCommandPaths = [];
        $this->currentDotenvPaths = [];
        $this->currentDynamicVariables = [];
        $this->currentConstants = [];
        $this->templates = [];
        $this->hidden = false;
    }
}
