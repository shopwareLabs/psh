<?php
namespace Shopware\Psh\Config;

class CompositeConfigLoader implements ConfigLoader
{
    /** @var ConfigLoader[] */
    private $configLoaders = [];

    /** @var ConfigBuilder */
    private $configBuilder;

    public function __construct(ConfigBuilder $configBuilder, ConfigLoader ...$configLoaders) {
        $this->configLoaders = $configLoaders;
        $this->configBuilder = $configBuilder;
    }

    public function isSupported(): bool
    {
        return 0 !== count($this->configLoaders);
    }

    public function load(): Config
    {
        $this->configBuilder->start();

        $header = null;
        $commandPaths = [];
        $dynamicVariables = [];
        $constants = [];
        $templates = [];

        $environmentSpecificConfigurations = [];
        foreach($this->configLoaders as $configLoader) {
            $config = $configLoader->load();

            $header = $config->getHeader();
            $commandPaths[] = array_map(function(ScriptPath $scriptPath) {
                return $scriptPath->getPath();
            }, $config->getAllScriptPaths());
            $dynamicVariables[] = $config->getDynamicVariables();
            $constants[] = $config->getConstants();
            $templates[] = $config->getTemplates();

            foreach($config->getEnvironments() as $environment) {
                $environmentSpecificConfigurations[$environment]['dynamicVariables'][] = $config->getDynamicVariables($environment);
                $environmentSpecificConfigurations[$environment]['constants'][] = $config->getConstants($environment);
                $environmentSpecificConfigurations[$environment]['templates'][] = $config->getTemplates($environment);
            }
        }

        $commandPaths = array_merge(...$commandPaths);
        $dynamicVariables = array_merge(...$dynamicVariables);
        $constants = array_merge(...$constants);
        $templates = array_merge(...$templates);

        $this->configBuilder->setHeader($header);
        $this->configBuilder->setCommandPaths($commandPaths);
        $this->configBuilder->setDynamicVariables($dynamicVariables);
        $this->configBuilder->setConstants($constants);
        $this->configBuilder->setTemplates($templates);

        foreach ($environmentSpecificConfigurations as $environment => $configurationData) {
            $this->configBuilder->start($environment);

            $dynamicVariables = array_merge(...$configurationData['dynamicVariables']);
            $constants = array_merge(...$configurationData['constants']);
            $templates = array_merge(...$configurationData['templates']);

            $this->configBuilder->setDynamicVariables($dynamicVariables);
            $this->configBuilder->setConstants($constants);
            $this->configBuilder->setTemplates($templates);
        }

        return $this->configBuilder->create();
    }
}
