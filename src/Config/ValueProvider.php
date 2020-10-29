<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

/**
 * Enables different sources to provide and lazy load values for the templates
 */
interface ValueProvider
{
    public function getValue(): string;
}
