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
        foreach ($override->getEnvironments() as $name => $overrideEnv) {
            $originalConfigEnv = $config->getEnvironments()[$name];

            $environments[$name] = new ConfigEnvironment(
                $this->overrideCommandPaths($originalConfigEnv, $overrideEnv),
                $this->mergeDynamicVariables($config->getEnvironments()[$name], $overrideEnv),
                $this->mergeConstants($config->getEnvironments()[$name], $overrideEnv),
                $this->overrideTemplates($config->getEnvironments()[$name], $overrideEnv),
                $this->overrideDotenvPaths($originalConfigEnv, $overrideEnv)
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
     * @return array|ScriptPath[]
     */
    private function overrideDotenvPaths(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        if ($overrideConfigEnv->getDotenvPaths()) {
            return $overrideConfigEnv->getDotenvPaths();
        }

        return $configEnvironment->getDotenvPaths();
    }

    /**
     * @param ConfigEnvironment $configEnvironment
     * @param ConfigEnvironment $overrideConfigEnv
     * @return array|ScriptPath[]
     */
    private function overrideCommandPaths(ConfigEnvironment $configEnvironment, ConfigEnvironment $overrideConfigEnv): array
    {
        if ($overrideConfigEnv->getAllScriptPaths()) {
            return $overrideConfigEnv->getAllScriptPaths();
        }

        return $configEnvironment->getAllScriptPaths();
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
}
