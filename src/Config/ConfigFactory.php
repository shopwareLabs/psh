<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use function array_merge;
use function array_pop;
use function array_shift;
use function count;
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
     * @var ConfigLoader[]
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
        string $fromPath,
        array $configFiles,
        array $overwrittenConsts
    ): array {
        $configs = [];
        $additionalConfigs = [[]];

        foreach ($configFiles as $configFile) {
            foreach ($this->configLoaders as $configLoader) {
                if (!$configLoader->isSupported($configFile)) {
                    continue;
                }

                $config = $configLoader
                    ->load($configFile, $overwrittenConsts);

                $additionalConfigs[] = $this
                    ->loadImports($config, $fromPath, $overwrittenConsts);

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
        array $overwrittenConsts
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
                    ->gatherConfigs($foundOccurrence, $foundConfigFiles, $overwrittenConsts);
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
        $mainConfig = array_shift($configs);

        while (count($configs) !== 0) {
            $mainConfig = $this->configMerger->mergeImport($mainConfig, array_pop($configs));
        }

        return $mainConfig;
    }
}
