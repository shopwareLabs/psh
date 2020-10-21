<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

class RequiredValue
{
    private $name;

    private $description;

    public function __construct(string $name, ?string $description = null)
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function hasDescription(): bool
    {
        return (bool) $this->description;
    }
}
