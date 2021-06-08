<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Shopware\Psh\Application\RuntimeParameters;

/**
 * Load configuration data from a file
 */
interface ConfigFileLoader
{
    public function isSupported(string $file): bool;

    public function load(string $file, RuntimeParameters $runtimeParameters): Config;
}
