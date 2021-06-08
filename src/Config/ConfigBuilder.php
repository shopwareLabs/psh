<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Shopware\Psh\Application\RuntimeParameters;
use function pathinfo;

/**
 * Builder pattern
 *
 * Create a config from a more complicated proto format by representing a stateful representation of config read so far.
 */
class ConfigBuilder
{
    private const DEFAULT_ENV = '##default##';

    private $header = '';

    private $environments = [];

    /**
     * @var string|null
     */
    private $currentEnvironment;

    private $currentCommandPaths;

    private $currentDotenvPaths;

    private $currentDynamicVariables;

    private $templates;

    private $currentConstants;

    private $hidden;

    private $currentRequiredVariables;

    private $imports;

    /**
     * @var string
     */
    private $workingDirectory;

    public function setHeader(?string $header = null): ConfigBuilder
    {
        $this->header = $header;

        return $this;
    }

    public function setWorkingDirectory(string $workingDirectory): ConfigBuilder
    {
        $this->workingDirectory = $workingDirectory;

        return $this;
    }

    public function start(?string $environment = null): ConfigBuilder
    {
        $this->reset();
        if ($environment === null) {
            $environment = self::DEFAULT_ENV;
        }

        $this->currentEnvironment = $environment;

        return $this;
    }

    public function setHidden(bool $set): ConfigBuilder
    {
        $this->hidden = $set;

        return $this;
    }

    public function setCommandPaths(array $commandPaths): ConfigBuilder
    {
        $this->currentCommandPaths = [];

        foreach ($commandPaths as $path) {
            $env = $this->currentEnvironment;

            if ($env === self::DEFAULT_ENV) {
                $env = null;
            }

            $this->currentCommandPaths[] = new ScriptsPath($path, $this->workingDirectory, $this->hidden, $env);
        }

        return $this;
    }

    /**
     * @deprecated only used by yaml builder
     */
    public function setDotenvPaths(array $dotenvPaths): ConfigBuilder
    {
        $this->currentDotenvPaths = [];

        foreach ($dotenvPaths as $dotenvPath) {
            $this->addDotenvPath($dotenvPath);
        }

        return $this;
    }

    public function addDotenvPath(string $dotenvPath): ConfigBuilder
    {
        $this->currentDotenvPaths[pathinfo($dotenvPath, PATHINFO_BASENAME)] = $dotenvPath;

        return $this;
    }

    public function addRequirePlaceholder(string $name, ?string $description = null): ConfigBuilder
    {
        $this->currentRequiredVariables[$name] = $description;

        return $this;
    }

    /**
     * @deprecated only used by yaml builder
     */
    public function setDynamicVariables(array $dynamicVariables): ConfigBuilder
    {
        $this->currentDynamicVariables = $dynamicVariables;

        return $this;
    }

    public function addDynamicVariable(string $key, string $value): ConfigBuilder
    {
        $this->currentDynamicVariables[$key] = $value;

        return $this;
    }

    /**
     * @deprecated only used by yaml builder
     */
    public function setConstants(array $constants): ConfigBuilder
    {
        $this->currentConstants = $constants;

        return $this;
    }

    public function addConstVariable(string $key, string $value): ConfigBuilder
    {
        $this->currentConstants[$key] = $value;

        return $this;
    }

    public function addImport(string $path): self
    {
        $this->imports[] = $path;

        return $this;
    }

    public function setTemplates(array $templates, string $baseFile): ConfigBuilder
    {
        $this->templates = [];

        foreach ($templates as $template) {
            $this->templates[] = new Template($template['source'], $template['destination'], $baseFile);
        }

        return $this;
    }

    public function create(RuntimeParameters $runtimeParameters): Config
    {
        $this->reset();

        return new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $this->environments, $runtimeParameters, $this->header);
    }

    private function reset(): void
    {
        if ($this->currentEnvironment) {
            $this->environments[$this->currentEnvironment] = new ConfigEnvironment(
                $this->hidden,
                $this->currentCommandPaths,
                $this->currentDynamicVariables,
                $this->currentConstants,
                $this->templates,
                $this->currentDotenvPaths,
                $this->currentRequiredVariables,
                $this->imports
            );
        }

        $this->currentCommandPaths = [];
        $this->currentDotenvPaths = [];
        $this->currentDynamicVariables = [];
        $this->currentConstants = [];
        $this->templates = [];
        $this->hidden = false;
        $this->currentRequiredVariables = [];
        $this->imports = [];
    }
}
