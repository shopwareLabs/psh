<?php declare (strict_types = 1);


namespace Shopware\Psh\ScriptRuntime;

/**
 * Enables different sources to provide and lazy load values for the templates
 */
interface ValueProvider
{
    /**
     * @return string
     */
    public function getValue(): string;
}
