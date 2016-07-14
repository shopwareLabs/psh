<?php declare(strict_types = 1);


namespace Shopware\Psh\ScriptRuntime;


class SimpleValueProvider implements ValueProvider
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $name
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return  $this->value;
    }
}