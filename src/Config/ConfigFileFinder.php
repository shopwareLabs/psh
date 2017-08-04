<?php declare(strict_types=1);


namespace Shopware\Psh\Config;

/**
 * Resolve the path to the config file
 */
class ConfigFileFinder
{
    const VALID_FILE_NAME_GLOB = '.psh.*';

    public function discoverFiles(string $fromDirectory): array
    {
        $configFiles = [];
        $currentDirectory = $fromDirectory;

        do {
            $globResult = glob($currentDirectory . '/' . self::VALID_FILE_NAME_GLOB);

            if (count($globResult) === 1) {
                return $globResult;
            }

            if (count($globResult) > 1) {
                foreach ($globResult as $configFile) {
                    $extension = pathinfo($configFile, PATHINFO_EXTENSION);
                    if (!in_array($extension, ['dist', 'override'], true)) {
                        $configFiles[0] = $configFile;
                    } elseif ($extension === 'dist' && !isset($configFiles[0])) {
                        $configFiles[0] = $configFile;
                    } elseif ($extension === 'override') {
                        $configFiles[1] = $configFile;
                    }
                }

                return $configFiles;
            }

            $currentDirectory = dirname($currentDirectory);
        } while ($currentDirectory !== '/');

        throw new \RuntimeException('No config file found, make sure you have created a .psh file');
    }
}
