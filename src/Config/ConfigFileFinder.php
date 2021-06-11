<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use function array_filter;
use function array_merge;
use function count;
use function dirname;
use function glob;
use function is_array;
use function pathinfo;

/**
 * Resolve the path to the config file
 */
class ConfigFileFinder
{
    private const VALID_FILE_NAME_GLOB = '.psh.*';

    public function discoverFiles(string $fromDirectory): array
    {
        $files = $this->findFirstDirectoryWithConfigFile($fromDirectory);

        return $this->determineResultInDirectory($files);
    }

    public function discoverConfigInDirectory(string $directory): array
    {
        $globResult = glob($directory . '/' . self::VALID_FILE_NAME_GLOB);

        if (!is_array($globResult) || count($globResult) === 0) {
            return [];
        }

        return $this->determineResultInDirectory($globResult);
    }

    private function findFirstDirectoryWithConfigFile(string $fromDirectory): array
    {
        $currentDirectory = $fromDirectory;

        do {
            $globResult = glob($currentDirectory . '/' . self::VALID_FILE_NAME_GLOB);

            if (is_array($globResult) && count($globResult) > 0) {
                return $globResult;
            }

            $currentDirectory = dirname($currentDirectory);
        } while ($currentDirectory !== '/');

        throw new NoConfigFileFound();
    }

    /**
     * @internal
     */
    public function determineResultInDirectory(array $configFileCandidates): array
    {
        if (count($configFileCandidates) === 1) {
            return $configFileCandidates;
        }

        $overrideFiles = array_filter($configFileCandidates, static function (string $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            return $extension === 'override';
        });

        $distFiles = array_filter($configFileCandidates, static function (string $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            return $extension === 'dist';
        });

        $configFiles = array_filter($configFileCandidates, static function (string $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            return $extension !== 'override' && $extension !== 'dist';
        });

        if (count($configFiles) > 0) {
            return array_merge($configFiles, $overrideFiles);
        }

        return array_merge($distFiles, $overrideFiles);
    }
}
