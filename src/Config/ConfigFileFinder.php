<?php declare(strict_types=1);


namespace Shopware\Psh\Config;

/**
 * Resolve the path to the config file
 */
class ConfigFileFinder
{
    const VALID_FILE_NAME_GLOB = '.psh.*';

    public function discoverFile(string $fromDirectory): string
    {
        $currentDirectory = $fromDirectory;
        do {
            $globResult = glob($currentDirectory . '/' . self::VALID_FILE_NAME_GLOB);

            if (count($globResult)) {
                return $this->preferNonDistributionFiles($globResult);
            }

            $currentDirectory = dirname($currentDirectory);
        } while ($currentDirectory !== '/');

        throw new \RuntimeException('No config file found, make sure you have created a .psh file');
    }

    /**
     * @param string[] $configFiles
     * @return string
     */
    private function preferNonDistributionFiles(array $configFiles): string
    {
        foreach ($configFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'dist') {
                return $file;
            }
        }

        return $configFiles[0];
    }
}
