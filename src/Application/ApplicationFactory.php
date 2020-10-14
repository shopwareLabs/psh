<?php declare(strict_types=1);


namespace Shopware\Psh\Application;

use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigBuilder;
use Shopware\Psh\Config\ConfigFileFinder;
use Shopware\Psh\Config\ConfigMerger;
use Shopware\Psh\Config\XmlConfigFileLoader;
use Shopware\Psh\Config\YamlConfigFileLoader;
use Shopware\Psh\Listing\DescriptionReader;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\ScriptRuntime\Command;
use Shopware\Psh\ScriptRuntime\Execution\Logger;
use Shopware\Psh\ScriptRuntime\Execution\ProcessEnvironment;
use Shopware\Psh\ScriptRuntime\Execution\ProcessExecutor;
use Shopware\Psh\ScriptRuntime\Execution\TemplateEngine;
use Shopware\Psh\ScriptRuntime\ScriptLoader\BashScriptParser;
use Shopware\Psh\ScriptRuntime\ScriptLoader\CommandBuilder;
use Shopware\Psh\ScriptRuntime\ScriptLoader\PshScriptParser;
use Shopware\Psh\ScriptRuntime\ScriptLoader\ScriptLoader;
use Symfony\Component\Yaml\Parser;

/**
 * Create the various interdependent objects for the application.
 */
class ApplicationFactory
{
    /**
     * @param string $rootDirectory
     * @param array $params
     * @return Config
     * @throws \RuntimeException
     */
    public function createConfig(string $rootDirectory, array $params): Config
    {
        $overwrittenConsts = (new ParameterParser())->parseParams($params);
        $configFinder = new ConfigFileFinder();
        $configFiles = $configFinder->discoverFiles($rootDirectory);

        $configLoaders = [
            new YamlConfigFileLoader(new Parser(), new ConfigBuilder(), $rootDirectory),
            new XmlConfigFileLoader(new ConfigBuilder(), $rootDirectory),
        ];

        $configs = [];
        foreach ($configFiles as $configFile) {
            foreach ($configLoaders as $configLoader) {
                if (!$configLoader->isSupported($configFile)) {
                    continue;
                }

                $configs[] = $configLoader->load($configFile, $overwrittenConsts);
            }
        }

        if (count($configs) === 0) {
            throw new \RuntimeException('Unable to read any configuration from "' . implode(', ', $configFiles) . '"');
        }

        $merger = new ConfigMerger();
        
        return $merger->merge(...$configs);
    }

    /**
     * @param Config $config
     * @return ScriptFinder
     */
    public function createScriptFinder(Config $config): ScriptFinder
    {
        return new ScriptFinder($config->getAllScriptsPaths(), new DescriptionReader());
    }

    /**
     * @param Script $script
     * @param Config $config
     * @param Logger $logger
     * @param string $rootDirectory
     * @return ProcessExecutor
     */
    public function createProcessExecutor(
        Script $script,
        Config $config,
        Logger $logger,
        string $rootDirectory
    ): ProcessExecutor {
        return new ProcessExecutor(
            new ProcessEnvironment(
                $config->getConstants($script->getEnvironment()),
                $config->getDynamicVariables($script->getEnvironment()),
                $config->getTemplates($script->getEnvironment()),
                $config->getDotenvPaths($script->getEnvironment())
            ),
            new TemplateEngine(),
            $logger,
            $rootDirectory
        );
    }

    /**
     * @param Script $script
     * @param ScriptFinder $scriptFinder
     *
     * @return Command[]
     */
    public function createCommands(Script $script, ScriptFinder $scriptFinder): array
    {
        $scriptLoader = new ScriptLoader(
            new BashScriptParser(),
            new PshScriptParser(new CommandBuilder(), $scriptFinder)
        );
        return $scriptLoader->loadScript($script);
    }

    /**
     * @param $directory
     * @return array
     */
    public function getConfigFiles(string $directory): array
    {
        return  (new ConfigFileFinder())->discoverFiles($directory);
    }
}
