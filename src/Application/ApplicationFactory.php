<?php declare(strict_types=1);


namespace Shopware\Psh\Application;

use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigBuilder;
use Shopware\Psh\Config\ConfigFileFinder;
use Shopware\Psh\Config\YamlConfigFileLoader;
use Shopware\Psh\Listing\DescriptionReader;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\ScriptRuntime\ProcessCommand;
use Shopware\Psh\ScriptRuntime\CommandBuilder;
use Shopware\Psh\ScriptRuntime\Logger;
use Shopware\Psh\ScriptRuntime\ProcessEnvironment;
use Shopware\Psh\ScriptRuntime\ProcessExecutor;
use Shopware\Psh\ScriptRuntime\ScriptLoader;
use Shopware\Psh\ScriptRuntime\TemplateEngine;
use Symfony\Component\Yaml\Parser;

/**
 * Create the various interdependent objects for the application.
 */
class ApplicationFactory
{
    /**
     * @param string $rootDirectory
     * @return Config
     * @throws \RuntimeException
     */
    public function createConfig(string $rootDirectory): Config
    {
        $configFinder = new ConfigFileFinder();
        $configFiles = $configFinder->discoverFiles($rootDirectory);

        $configLoader = new YamlConfigFileLoader(new Parser(), new ConfigBuilder());

        $configs = [];
        foreach ($configFiles as $configFile) {
            if (!$configLoader->isSupported($configFile)) {
                throw new \RuntimeException('Unable to read configuration from "' . $configFile . '"');
            }

            $configs[] = $configLoader->load($configFile);
        }

        return $configs[0];
    }

    /**
     * @param Config $config
     * @return ScriptFinder
     */
    public function createScriptFinder(Config $config): ScriptFinder
    {
        return new ScriptFinder($config->getAllScriptPaths(), new DescriptionReader());
    }

    /**
     * @param Script $script
     * @param Config $config
     * @param Logger $logger
     * @param string $rootDirectory
     * @return ProcessExecutor
     */
    public function createProcessExecutor(Script $script, Config $config, Logger $logger, string $rootDirectory): ProcessExecutor
    {
        return  new ProcessExecutor(
            new ProcessEnvironment(
                $config->getConstants($script->getEnvironment()),
                $config->getDynamicVariables($script->getEnvironment()),
                $config->getTemplates($script->getEnvironment())
            ),
            new TemplateEngine(),
            $logger,
            $rootDirectory
        );
    }

    /**
     * @param Script $script
     * @return ProcessCommand[]
     */
    public function createCommands(Script $script): array
    {
        $scriptLoader = new ScriptLoader(new CommandBuilder());
        return $scriptLoader->loadScript($script);
    }
}
