<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

/**
 * Load configuration data from a file
 */
interface ConfigLoader
{
    public function isSupported(string $file): bool;

    public function load(string $file, array $params): Config;
}
