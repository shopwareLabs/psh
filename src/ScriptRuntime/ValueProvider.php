<?php declare(strict_types = 1);


namespace Shopware\Psh\ScriptRuntime;


interface ValueProvider
{
    /**
     * @return string
     */
    public function getValue(): string;
}