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

            $environments[$name] = new EnvironmentBag(
                $this->overridePaths($originalConfigEnv, $overrideEnv),
                $this->mergeDynamicVariables($config->getEnvironments()[$name], $overrideEnv),
                $this->mergeConstants($config->getEnvironments()[$name], $overrideEnv),
                $this->overrideTemplates($config->getEnvironments()[$name], $overrideEnv)
            );
        }

        return $environments;
    }

    /**
     * @param EnvironmentBag $configEnvironment
     * @param EnvironmentBag $overrideEnv
     * @return array
     */
    private function mergeDynamicVariables(EnvironmentBag $configEnvironment, EnvironmentBag $overrideEnv): array
    {
        return array_merge($configEnvironment->getDynamicVariables(), $overrideEnv->getDynamicVariables());
    }

    /**
     * @param EnvironmentBag $configEnvironment
     * @param EnvironmentBag $overrideConfigEnv
     * @return array|ScriptPath[]
     */
    private function overridePaths(EnvironmentBag $configEnvironment, EnvironmentBag $overrideConfigEnv): array
    {
        if ($overrideConfigEnv->getAllScriptPaths()) {
            return $overrideConfigEnv->getAllScriptPaths();
        }

        return $configEnvironment->getAllScriptPaths();
    }

    /**
     * @param EnvironmentBag $configEnvironment
     * @param EnvironmentBag $overrideConfigEnv
     * @return array
     */
    private function mergeConstants(EnvironmentBag $configEnvironment, EnvironmentBag $overrideConfigEnv): array
    {
        return array_merge($configEnvironment->getConstants(), $overrideConfigEnv->getConstants());
    }

    /**
     * @param EnvironmentBag $configEnvironment
     * @param $overrideConfigEnv
     * @return array
     */
    private function overrideTemplates(EnvironmentBag $configEnvironment, EnvironmentBag $overrideConfigEnv): array
    {
        if ($overrideConfigEnv->getTemplates()) {
            return $overrideConfigEnv->getTemplates();
        }

        return $configEnvironment->getTemplates();
    }
}
