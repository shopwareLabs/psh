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

    private $currentCommandPaths = [];

    private $currentDynamicVariables = [];

    private $templates = [];

    private $currentConstants = [];

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
        if (!$environment) {
            $environment = self::DEFAULT_ENV;
        }

        $this->currentEnvironment = $environment;
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
     * @param array $dynamicVariables
     * @return ConfigBuilder
     */
    public function setDynamicVariables(array $dynamicVariables): ConfigBuilder
    {
        $this->currentDynamicVariables = $dynamicVariables;
        return $this;
    }

    /**
     * @param array $constants
     * @return ConfigBuilder
     */
    public function setConstants(array $constants): ConfigBuilder
    {
        $this->currentConstants = $constants;
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
    public function create(): Config
    {
        $this->reset();
        return new Config($this->header, self::DEFAULT_ENV, $this->environments);
    }

    private function reset()
    {
        if ($this->currentEnvironment) {
            $this->environments[$this->currentEnvironment] = new ConfigEnvironment(
                $this->currentCommandPaths,
                $this->currentDynamicVariables,
                $this->currentConstants,
                $this->templates
            );
        }

        $this->currentCommandPaths = [];
        $this->currentDynamicVariables = [];
        $this->currentConstants = [];
        $this->templates = [];
    }
}
