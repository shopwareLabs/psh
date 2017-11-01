<?php declare(strict_types=1);

namespace Shopware\Psh\ConfigLoad;

class ConfigFileDiscovery
{
    /**
     * @var string
     */
    private $primaryFile;
    /**
     * @var string
     */
    private $overrideFile;

    public function __construct(string $primaryFile, string $overrideFile)
    {
        $this->primaryFile = $primaryFile;
        $this->overrideFile = $overrideFile;
    }

    /**
     * @return string
     */
    public function getPrimaryFile(): string
    {
        return $this->primaryFile;
    }

    /**
     * @return string
     */
    public function getOverrideFile(): string
    {
        return $this->overrideFile;
    }
}
