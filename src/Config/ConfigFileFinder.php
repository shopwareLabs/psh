<?php declare(strict_types = 1);


namespace Shopware\Psh\Config;


class ConfigFileFinder
{
    const VALID_FILE_NAME_GLOB = '.psh.*';

    public function discoverFile(string $fromDirectory): string
    {
        $currentDirectory = $fromDirectory;
        do {
            $globResult = glob($currentDirectory . '/' . self::VALID_FILE_NAME_GLOB);

            if(count($globResult)) {
                return $globResult[0];
            }

            $currentDirectory = dirname($currentDirectory);
        } while($currentDirectory !== '/');

        throw new \RuntimeException('No config file found, make sure you have created a .psh file');
    }

}