<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use function file_exists;
use function file_get_contents;
use function pathinfo;

trait ConfigFileLoaderFileSystemHandlers
{
    private function loadFileContents(string $file): string
    {
        return file_get_contents($file);
    }

    private function fixPath(
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

        throw new InvalidReferencedPath($absoluteOrRelativePath, $possiblyValidFiles);
    }

    private function makeAbsolutePath(string $baseFile, string $path): string
    {
        if ($path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }

        return $this->getWorkingDir($baseFile) . '/' . $path;
    }

    private function getWorkingDir(string $baseFile): string
    {
        return pathinfo($baseFile, PATHINFO_DIRNAME);
    }
}
