<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

interface ConfigLogger
{
    public function mainConfigFiles(string $mainFile, string $overrideFile = null): void;

    public function notifyImportNotFound(string $import): void;

    public function importConfigFiles(string $import, string $mainFile, string $overrideFile = null): void;
}
