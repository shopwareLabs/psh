<?php declare (strict_types=1);


namespace Shopware\Psh\Config;

/**
 * Resolve the path to the config file
 */
class ConfigFileFinder
{
    const VALID_FILE_NAME_GLOB = '.psh.*';

    public function discoverFiles(string $fromDirectory): array
    {
        $files = $this->findFirstDirectoryWithConfigFile($fromDirectory);

        return $this->determineResultInDirectory($files);
    }

    /**
     * @param string $fromDirectory
     * @return array
     */
    public function findFirstDirectoryWithConfigFile(string $fromDirectory): array
    {
        $currentDirectory = $fromDirectory;

        do {
            $globResult = glob($currentDirectory . '/' . self::VALID_FILE_NAME_GLOB);

            if (is_array($globResult) && count($globResult) > 0) {
                return $globResult;
            }

            $currentDirectory = dirname($currentDirectory);
        } while ($currentDirectory !== '/');

        throw new \RuntimeException('No config file found, make sure you have created a .psh file');
    }

    /**
     * @param array $globResult
     * @return array
     */
    public function determineResultInDirectory(array $globResult): array
    {
        if (count($globResult) === 1) {
            return $globResult;
        }

        $overrideFiles = array_filter($globResult, function (string $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            return $extension === 'override';
        });

        $distFiles = array_filter($globResult, function (string $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            return $extension === 'dist';
        });

        $configFiles = array_filter($globResult, function (string $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            return $extension !== 'override' && $extension !== 'dist';
        });

        if (count($configFiles) > 0) {
            return array_merge($configFiles, $overrideFiles);
        }

        return array_merge($distFiles, $overrideFiles);
    }
}
