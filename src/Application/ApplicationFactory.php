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

                $configs[] = $configLoader->load($configFile, $this->reformatParams($params));
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
     * @param array $params
     * @return array
     */
    private function reformatParams(array $params): array
    {
        if (count($params) < 2) {
            return [];
        }

        $reformattedParams = [];
        $paramsCount = count($params);
        for ($i = 2; $i < $paramsCount; $i++) {
            $key = $params[$i];

            if (strpos($key, '--') !== 0) {
                throw new InvalidParameterException(
                    sprintf('Unable to parse parameter %s. Use -- for correct usage', $key)
                );
            }

            if (strpos($key, '=')) {
                list($key, $value) = explode('=', $key, 2);

                if (strpos($value, '"') === 0) {
                    $value = substr($value, 1, -1);
                }
            } else {
                $i++;
                $value = $params[$i];
            }

            $key = str_replace('--', '', $key);
            $reformattedParams[strtoupper($key)] = $value;
        }

        return $reformattedParams;
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
