<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

class ScriptPath
{
    /**
     * @var string
     */
    private $namespace;
    /**
     * @var string
     */
    private $path;

    /**
     * @param string $namespace
     * @param string $path
     */
    public function __construct(string $path, string $namespace = null)
    {
        $this->namespace = $namespace;
        $this->path = $path;
    }

    /**
     * @return string|null
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
