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

            if (count($globResult)) {
                $configFiles[] = $this->preferNonDistributionFiles($globResult);
                if ($overrideFile = $this->getOverrideFile($globResult)) {
                    $configFiles[] = $overrideFile ;
                }

                return $configFiles;
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
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
            if ($fileExtension !== 'yaml' || $fileExtension !== 'yml') {
                return $file;
            }
        }

        return $configFiles[0];
    }

    /**
     * @param array $configFiles
     * @return string
     */
    private function getOverrideFile(array $configFiles): string
    {
        foreach ($configFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'override') {
                return $file;
            }
        }

        return '';
    }
}
