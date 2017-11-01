<?php declare(strict_types=1);


namespace Shopware\Psh\ConfigLoad;

/**
 * Resolve the path to the config file
 */
class ConfigFileFinder
{
    const VALID_FILE_NAME_GLOB = '.psh.*';

    public function discoverFiles(string $fromDirectory): ConfigFileDiscovery
    {
        $files = $this->findFirstDirectoryWithConfigFile($fromDirectory);

        return new ConfigFileDiscovery(
            $this->determinePrimaryFile($files),
            $this->determineOverrideFile($files)
        );
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

            if ($globResult) {
                return $globResult;
            }

            $currentDirectory = dirname($currentDirectory);
        } while ($currentDirectory !== '/');

        throw new \RuntimeException('No config file found, make sure you have created a .psh file');
    }

    /**
     * @param array $globResult
     * @return string
     */
    private function determineOverrideFile(array $globResult): string
    {
        $filterResult = array_filter($globResult, function (string $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            return $extension === 'override';
        });

        return (string) array_shift($filterResult);
    }

    /**
     * @param array $globResult
     * @return string
     */
    private function determinePrimaryFile(array $globResult): string
    {
        $filterResult = array_filter($globResult, function (string $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            return $extension !== 'override';
        });

        return array_shift($filterResult);
    }
}
