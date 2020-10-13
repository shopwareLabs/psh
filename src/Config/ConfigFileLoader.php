<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use InvalidArgumentException;
use function file_exists;
use function file_get_contents;
use function pathinfo;
use function print_r;
use function sprintf;

abstract class ConfigFileLoader implements ConfigLoader
{
    protected function loadFileContents(string $file): string
    {
        return file_get_contents($file);
    }

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

        throw new InvalidArgumentException(sprintf(
            'Unable to find a file referenced by "%s", tried: %s',
            $absoluteOrRelativePath,
            print_r($possiblyValidFiles, true)
        ));
    }

    protected function makeAbsolutePath(string $baseFile, string $path): string
    {
        return pathinfo($baseFile, PATHINFO_DIRNAME) . '/' . $path;
    }
}
