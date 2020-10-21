<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use function is_dir;

class ScriptsPath
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var bool
     */
    private $hidden;

    /**
     * @var string
     */
    private $path;

    /**
     * @param string $namespace
     */
    public function __construct(
        string $path,
        bool $hidden,
        ?string $namespace = null
    ) {
        $this->namespace = $namespace;
        $this->hidden = $hidden;
        $this->path = $path;
    }

    /**
     * @return string|null
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function isValid(): bool
    {
        return is_dir($this->path);
    }
}
