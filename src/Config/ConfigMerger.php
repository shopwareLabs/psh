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
        if ($override === null) {
            return $config;
        }

        $header = $config->getHeader();
        $defaultEnvironment = $config->getDefaultEnvironment();
        $environments = $config->getEnvironments();

        if ($override->getHeader()) {
            $header = $override->getHeader();
        }

        if ($override->getEnvironments()) {
            $environments = $override->getEnvironments();
        }

        if ($override->getDefaultEnvironment()) {
            $defaultEnvironment = $override->getDefaultEnvironment();
        }

        return new Config($header, $defaultEnvironment, $environments);
    }
}