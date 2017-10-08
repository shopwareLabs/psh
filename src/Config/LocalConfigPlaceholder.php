<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

class LocalConfigPlaceholder
{

    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $description;
    /**
     * @var string
     */
    private $default;
    /**
     * @var bool
     */
    private $storable;

    public function __construct(string $name, string $description = '', string $default = '', bool $storable = true)
    {
        $this->name = $name;
        $this->description = $description;
        $this->default = $default;
        $this->storable = $storable;
    }

    public function isValid(): bool
    {
        if (!$this->description && !$this->default) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getDefault(): string
    {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function isStorable(): bool
    {
        return $this->storable;
    }
}
