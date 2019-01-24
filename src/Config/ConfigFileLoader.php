<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

abstract class ConfigFileLoader implements ConfigLoader
{

    /**
     * @param string $file
     * @return string
     */
    protected function loadFileContents(string $file): string
    {
        return file_get_contents($file);
    }

    /**
     * @param string $applicationRootDirectory
     * @param string $absoluteOrRelativePath
     * @param string $baseFile
     * @return string
     */
    protected function fixPath(
        string $applicationRootDirectory,
        string $absoluteOrRelativePath,
        string $baseFile
    ): string {
        $possiblyValidFiles = [
            $applicationRootDirectory . '/' . $absoluteOrRelativePath,
            $this->makeAbsolutePath($baseFile, $absoluteOrRelativePath),
            $absoluteOrRelativePath,
        ];

        foreach ($possiblyValidFiles as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Unable to find a file referenced by "%s", tried: %s',
            $absoluteOrRelativePath,
            print_r($possiblyValidFiles, true)
        ));
    }

    /**
     * @param string $baseFile
     * @param string $path
     * @return string
     */
    protected function makeAbsolutePath(string $baseFile, string $path): string
    {
        return pathinfo($baseFile, PATHINFO_DIRNAME) . '/' . $path;
    }
}
