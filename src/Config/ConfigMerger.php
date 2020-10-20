<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use function array_keys;
use function array_merge;

class ConfigMerger
{
    public function mergeOverride(Config $config, Config $override = null): Config
    {
        if ($override === null) {
            return $config;
        }

        $header = $config->getHeader();
        $defaultEnvironment = $config->getDefaultEnvironment();
        $environments = $config->getEnvironments();

        if ($override->getHeader()) {
            $header = $override->getHeader();
        }

        if ($override->getEnvironments()) {
            $environments = $this->mergeConfigEnvironments($config, $override);
        }

        if ($override->getDefaultEnvironment()) {
            $defaultEnvironment = $override->getDefaultEnvironment();
        }

        return new Config(new EnvironmentResolver(), $defaultEnvironment, $environments, $config->getParams(), $header);
    }

    public function mergeImport(Config $config, Config $import = null): Config
    {
        if ($import === null) {
            return $config;
        }

        $header = $config->getHeader();
        $defaultEnvironment = $config->getDefaultEnvironment();
        $environments = $config->getEnvironments();

        if ($import->getEnvironments()) {
            $environments = $this->mergeConfigEnvironments($config, $import, true);
        }

        return new Config(new EnvironmentResolver(), $defaultEnvironment, $environments, $config->getParams(), $header);
    }

    private function mergeConfigEnvironments(Config $config, Config $override, bool $asImport = false): array
    {
        $environments = [];

        $foundEnvironments = array_keys(array_merge($config->getEnvironments(), $override->getEnvironments()));

        foreach ($foundEnvironments as $name) {
            if (!isset($override->getEnvironments()[$name])) {
                $environments[$name] = $config->getEnvironments()[$name];

                continue;
            }

            if (!isset($config->getEnvironments()[$name])) {
                $environments[$name] = $override->getEnvironments()[$name];

                continue;
            }

            if ($asImport) {
                $environments[$name] = $this
                    ->mergeEnvironmentsAsImport($config->getEnvironments()[$name], $override->getEnvironments()[$name]);
            } else {
                $environments[$name] = $this
                    ->mergeEnvironmentsAsOverride($config->getEnvironments()[$name], $override->getEnvironments()[$name]);
            }
        }

        return $environments;
    }

    private function mergeEnvironmentsAsOverride(ConfigEnvironment $original, ConfigEnvironment $override): ConfigEnvironment
    {
        return new ConfigEnvironment(
            $this->overrideHidden($original, $override),
            $this->overrideScriptsPaths($original, $override),
            $this->mergeDynamicVariables($original, $override),
            $this->mergeConstants($original, $override),
            $this->overrideTemplates($original, $override),
            $this->mergeDotenvPaths($original, $override)
        );
    }

    private function mergeEnvironmentsAsImport(ConfigEnvironment $original, ConfigEnvironment $override): ConfigEnvironment
    {
        return new ConfigEnvironment(
            $this->overrideHidden($original, $override),
            $this->mergeScriptsPaths($original, $override),
            $this->mergeDynamicVariables($original, $override),
            $this->mergeConstants($original, $override),
            $this->mergeTemplates($original, $override),
            $this->mergeDotenvPaths($original, $override)
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

    /**
     * @param $overrideConfigEnv
     */
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
}
