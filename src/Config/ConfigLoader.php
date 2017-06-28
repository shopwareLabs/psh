<?php declare(strict_types=1);


namespace Shopware\Psh\Config;

/**
 * Load configuration data from a file
 */
interface ConfigLoader
{
    /**
     * @return bool
     */
    public function isSupported(): bool;

    /**
     * @return Config
     */
    public function load(): Config;
}
