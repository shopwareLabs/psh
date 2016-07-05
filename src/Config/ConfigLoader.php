<?php declare(strict_types = 1);


namespace Shopware\Psh\Config;


interface ConfigLoader
{
    public function isSupported(string $file): bool;

    public function load(string $file): Config;
}