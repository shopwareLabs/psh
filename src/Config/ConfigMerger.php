<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

class ConfigMerger
{
    /**
     * @param Config $config
     * @param Config|null $override
     * @return Config
     */
    public function merge(Config $config, Config $override = null): Config
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

        return new Config($header, $defaultEnvironment, $environments, $config->getParams());
    }

    /**
     * @param Config $config
     * @param Config $override
     * @return array
     */
    private function mergeConfigEnvironments(Config $config, Config $override): array
    {
        $environments = [];

        $foundEnvironments = array_keys(array_merge($config->getEnvironments(), $override->getEnvironments()));

        foreach ($foundEnvironments as $name) {
            if (!isset($override->getEnvironments()[$name])) {
                $environments[$name] = $config->getEnvironments();

                continue;
            }

            $overrideEnv = $override->getEnvironments()[$name];
            $originalConfigEnv = $config->getEnvironments()[$name] ?? new ConfigEnvironment();

            $environments[$name] = new ConfigEnvironment(
                $this->overrideHidden($originalConfigEnv, $overrideEnv),
                $this->overrideScriptsPaths($originalConfigEnv, $overrideEnv),
                $this->mergeDynamicVariables($originalConfigEnv, $overrideEnv),
                $this->mergeConstants($originalConfigEnv, $overrideEnv),
                $this->overrideTemplates($originalConfigEnv, $overrideEnv),
                $this->mergeDotenvPaths($originalConfigEnv, $overrideEnv)
            );
        }

        return $environments;
    }

    /**
     * @param ConfigEnvironment $configEnvironment
     * @param ConfigEnvironment $overrideEnv
     * @return array
     */
    private function mergeDynamicVariables(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideEnv): array
    {
        return array_merge($configEnvironment->getDynamicVariables(), $overrideEnv->getDynamicVariables());
    }

    /**
     * @param ConfigEnvironment $configEnvironment
     * @param ConfigEnvironment $overrideConfigEnv
     * @return ScriptsPath[]
     */
    private function mergeDotenvPaths(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        return array_merge($configEnvironment->getDotenvPaths(), $overrideConfigEnv->getDotenvPaths());
    }

    /**
     * @param ConfigEnvironment $configEnvironment
     * @param ConfigEnvironment $overrideConfigEnv
     * @return ScriptsPath[]
     */
    private function overrideScriptsPaths(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        if ($overrideConfigEnv->getAllScriptsPaths()) {
            return $overrideConfigEnv->getAllScriptsPaths();
        }

        return $configEnvironment->getAllScriptsPaths();
    }

    /**
     * @param ConfigEnvironment $configEnvironment
     * @param ConfigEnvironment $overrideConfigEnv
     * @return array
     */
    private function mergeConstants(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        return array_merge($configEnvironment->getConstants(), $overrideConfigEnv->getConstants());
    }

    /**
     * @param ConfigEnvironment $configEnvironment
     * @param $overrideConfigEnv
     * @return array
     */
    private function overrideTemplates(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        if ($overrideConfigEnv->getTemplates()) {
            return $overrideConfigEnv->getTemplates();
        }

        return $configEnvironment->getTemplates();
    }

    private function overrideHidden(ConfigEnvironment $originalConfigEnv, ConfigEnvironment $overrideEnv): bool
    {
        if ($overrideEnv->isHidden()) {
            return true;
        }

        return $originalConfigEnv->isHidden();
    }
}
