<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Shopware\Psh\Application\RuntimeParameters;
use function array_keys;
use function array_merge;

class ConfigMerger
{
    /**
     * @var RuntimeParameters
     */
    private $runtimeParameters;

    public function __construct(RuntimeParameters $runtimeParameters)
    {
        $this->runtimeParameters = $runtimeParameters;
    }

    public function mergeOverride(Config $config, ?Config $override = null): Config
    {
        if ($override === null) {
            return $config;
        }

        $header = $config->getHeader();
        $defaultEnvironment = $config->getDefaultEnvironment();

        if ($override->getHeader()) {
            $header = $override->getHeader();
        }

        $environments = $this->mergeOverrideConfigEnvironments($config, $override);

        if ($override->getDefaultEnvironment()) {
            $defaultEnvironment = $override->getDefaultEnvironment();
        }

        return new Config(new EnvironmentResolver(), $defaultEnvironment, $environments, $this->runtimeParameters, $header);
    }

    public function mergeImport(Config $config, ?Config $import = null): Config
    {
        if ($import === null) {
            return $config;
        }

        $header = $config->getHeader();
        $defaultEnvironment = $config->getDefaultEnvironment();
        $environments = $this->mergeImportConfigEnvironments($config, $import);

        return new Config(new EnvironmentResolver(), $defaultEnvironment, $environments, $this->runtimeParameters, $header);
    }

    private function mergeOverrideConfigEnvironments(Config $config, Config $override): array
    {
        return $this->mapEnvironments($config, $override, function (ConfigEnvironment $environment, ConfigEnvironment $overrideEnvironment) {
            return $this
                    ->mergeEnvironmentsAsOverride($environment, $overrideEnvironment);
        });
    }

    private function mergeImportConfigEnvironments(Config $config, Config $import): array
    {
        return $this->mapEnvironments($config, $import, function (ConfigEnvironment $environment, ConfigEnvironment $importEnvironment) {
            return $this
                    ->mergeEnvironmentsAsImport($environment, $importEnvironment);
        });
    }

    private function mergeEnvironmentsAsOverride(ConfigEnvironment $original, ConfigEnvironment $override): ConfigEnvironment
    {
        return new ConfigEnvironment(
            $this->overrideHidden($original, $override),
            $this->overrideScriptsPaths($original, $override),
            $this->mergeDynamicVariables($original, $override),
            $this->mergeConstants($original, $override),
            $this->overrideTemplates($original, $override),
            $this->mergeDotenvPaths($original, $override),
            $this->mergeRequiredVariables($original, $override)
        );
    }

    private function mergeEnvironmentsAsImport(ConfigEnvironment $original, ConfigEnvironment $import): ConfigEnvironment
    {
        return new ConfigEnvironment(
            $this->overrideHidden($original, $import),
            $this->mergeScriptsPaths($original, $import),
            $this->mergeDynamicVariables($original, $import),
            $this->mergeConstants($original, $import),
            $this->mergeTemplates($original, $import),
            $this->mergeDotenvPaths($original, $import),
            $this->mergeRequiredVariables($original, $import)
        );
    }

    private function mergeDynamicVariables(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideEnv): array
    {
        return array_merge($configEnvironment->getDynamicVariables(), $overrideEnv->getDynamicVariables());
    }

    /**
     * @return ScriptsPath[]
     */
    private function mergeDotenvPaths(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        return array_merge($configEnvironment->getDotenvPaths(), $overrideConfigEnv->getDotenvPaths());
    }

    /**
     * @return ScriptsPath[]
     */
    private function mergeRequiredVariables(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        return array_merge($configEnvironment->getRequiredVariables(), $overrideConfigEnv->getRequiredVariables());
    }

    /**
     * @return ScriptsPath[]
     */
    private function mergeScriptsPaths(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        return array_merge($configEnvironment->getAllScriptsPaths(), $overrideConfigEnv->getAllScriptsPaths());
    }

    /**
     * @return ScriptsPath[]
     */
    private function overrideScriptsPaths(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        if ($overrideConfigEnv->getAllScriptsPaths()) {
            return $overrideConfigEnv->getAllScriptsPaths();
        }

        return $configEnvironment->getAllScriptsPaths();
    }

    private function mergeConstants(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        return array_merge($configEnvironment->getConstants(), $overrideConfigEnv->getConstants());
    }

    private function overrideTemplates(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        if ($overrideConfigEnv->getTemplates()) {
            return $overrideConfigEnv->getTemplates();
        }

        return $configEnvironment->getTemplates();
    }

    private function mergeTemplates(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        return array_merge($configEnvironment->getTemplates(), $overrideConfigEnv->getTemplates());
    }

    private function overrideHidden(ConfigEnvironment $originalConfigEnv, ConfigEnvironment $overrideEnv): bool
    {
        if ($overrideEnv->isHidden()) {
            return true;
        }

        return $originalConfigEnv->isHidden();
    }

    private function getAllEnvironmentNames(Config $config, Config $override): array
    {
        return array_keys(array_merge($config->getEnvironments(), $override->getEnvironments()));
    }

    private function mapEnvironments(Config $config, Config $override, callable $closure): array
    {
        $environments = [];

        $foundEnvironments = $this->getAllEnvironmentNames($config, $override);
        foreach ($foundEnvironments as $name) {
            if (!isset($override->getEnvironments()[$name])) {
                $environments[$name] = $config->getEnvironments()[$name];

                continue;
            }

            if (!isset($config->getEnvironments()[$name])) {
                $environments[$name] = $override->getEnvironments()[$name];

                continue;
            }

            $environments[$name] = $closure($config->getEnvironments()[$name], $override->getEnvironments()[$name]);
        }

        return $environments;
    }
}
