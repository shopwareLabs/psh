<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use RuntimeException;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigBuilder;
use Shopware\Psh\Config\ConfigFactory;
use Shopware\Psh\Config\ConfigFileFinder;
use Shopware\Psh\Config\ConfigLogger;
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
use function count;
use function implode;

/**
 * Create the various interdependent objects for the application.
 */
class ApplicationFactory
{
    /**
     * @throws RuntimeException
     */
    public function createConfig(ConfigLogger $logger, string $rootDirectory, array $params): Config
    {
        $runtimeParameters = (new ParameterParser())->parseAllParams($params);

        $configFinder = new ConfigFileFinder();
        $configFiles = $configFinder->discoverFiles($rootDirectory);

        $merger = new ConfigMerger($runtimeParameters);
        $configLoaders = [
            new YamlConfigFileLoader(new Parser(), new ConfigBuilder(), $rootDirectory),
            new XmlConfigFileLoader(new ConfigBuilder(), $rootDirectory),
        ];

        $configFactory = new ConfigFactory($merger, $configFinder, $logger, $configLoaders);

        $logger->mainConfigFiles(...$configFiles);
        $configs = $configFactory->gatherConfigs($configFiles, $runtimeParameters);

        if (count($configs) === 0) {
            throw new RuntimeException('Unable to read any configuration from "' . implode(', ', $configFiles) . '"');
        }

        return $configFactory->mergeConfigs($configs);
    }

    public function createScriptFinder(Config $config): ScriptFinder
    {
        return new ScriptFinder($config->getAllScriptsPaths(), new DescriptionReader());
    }

    public function createProcessExecutor(
        Script $script,
        Config $config,
        Logger $logger
    ): ProcessExecutor {
        return new ProcessExecutor(
            new ProcessEnvironment(
                $config->getConstants($script->getEnvironment()),
                $config->getDynamicVariables($script->getEnvironment()),
                $config->getTemplates($script->getEnvironment()),
                $config->getDotenvVariables($script->getEnvironment())
            ),
            new TemplateEngine(),
            $logger
        );
    }

    /**
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
}
