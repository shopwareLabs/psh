<?php declare(strict_types=1);


namespace Shopware\Psh\ConfigLoad;

use Shopware\Psh\Config\Config;

/**
 * Load configuration data from a file
 */
interface ConfigLoader
{
    /**
     * @param ConfigFileDiscovery $file
     * @param array $params
     * @return Config
     */
    public function load(ConfigFileDiscovery $file, array $params): Config;
}
