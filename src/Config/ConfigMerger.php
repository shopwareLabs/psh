<?php declare(strict_types=1);


namespace Shopware\Psh\Config;


class ConfigMerger
{
    /**
     * @param Config $config
     * @return Config
     */
    public function merge(Config $config, Config $override = null): Config
    {
        if($override) {
            $config = new Config($override->getHeader(), $override->getDefaultEnvironment(), []);
        }
        return $config;
    }
}