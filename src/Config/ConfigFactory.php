<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Shopware\Psh\Application\RuntimeParameters;
use function array_merge;
use function array_pop;
use function count;
use function dirname;
use function glob;
use function is_dir;

class ConfigFactory
{
    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @var ConfigFileFinder
     */
    private $configFileFinder;

    /**
     * @var ConfigFileLoader[]
     */
    private $configLoaders;

    /**
     * @var ConfigLogger
     */
    private $configLogger;

    public function __construct(
        ConfigMerger $configMerger,
        ConfigFileFinder $configFileFinder,
        ConfigLogger $configLogger,
        array $configLoaders
    ) {
        $this->configMerger = $configMerger;
        $this->configFileFinder = $configFileFinder;
        $this->configLoaders = $configLoaders;
        $this->configLogger = $configLogger;
    }

    /**
     * @return Config[]
     */
    public function gatherConfigs(
        array $configFiles,
        RuntimeParameters $runtimeParameters
    ): array {
        $configs = [];
        $additionalConfigs = [[]];

        foreach ($configFiles as $configFile) {
            foreach ($this->configLoaders as $configLoader) {
                if (!$configLoader->isSupported($configFile)) {
                    continue;
                }

                $config = $configLoader
                    ->load($configFile, $runtimeParameters);

                $additionalConfigs[] = $this
                    ->loadImports($config, dirname($configFile), $runtimeParameters);

                $configs[] = $config;
            }
        }

        if (!$configs) {
            return [];
        }

        $config = $this->configMerger->mergeOverride(...$configs);

        return array_merge([$config], ...array_merge(...$additionalConfigs));
    }

    private function loadImports(
        Config $config,
        string $fromPath,
        RuntimeParameters $runtimeParameters
    ): array {
        $additionalConfigs = [];

        foreach ($config->getImports() as $importPath) {
            $foundSomething = false;
            foreach (glob($fromPath . '/' . $importPath) as $foundOccurrence) {
                if (!is_dir($foundOccurrence)) {
                    continue;
                }

                $foundConfigFiles = $this->configFileFinder
                    ->discoverConfigInDirectory($foundOccurrence);

                if (count($foundConfigFiles) === 0) {
                    continue;
                }

                $foundSomething = true;
                $this->configLogger->importConfigFiles($importPath, ...$foundConfigFiles);
                $additionalConfigs[] = $this
                    ->gatherConfigs($foundConfigFiles, $runtimeParameters);
            }

            if (!$foundSomething) {
                $this->configLogger->notifyImportNotFound($importPath);
            }
        }

        return $additionalConfigs;
    }

    /**
     * @param Config[] $configs
     */
    public function mergeConfigs(array $configs): Config
    {
        $mainConfig = array_pop($configs);

        while (count($configs) !== 0) {
            $mainConfig = $this->configMerger->mergeImport($mainConfig, array_pop($configs));
        }

        return $mainConfig;
    }
}
