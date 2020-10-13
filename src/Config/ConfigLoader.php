<?php declare (strict_types=1);


namespace Shopware\Psh\Config;

/**
 * Load configuration data from a file
 */
interface ConfigLoader
{
    /**
     * @param string $file
     * @return bool
     */
    public function isSupported(string $file): bool;

    /**
     * @param string $file
     * @param array $params
     * @return Config
     */
    public function load(string $file, array $params): Config;
}
