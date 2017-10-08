<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

interface PlaceholderResolver
{
    /**
     * Resolve a configured placeholder to a string value
     *
     * @param LocalConfigPlaceholder $placeholder
     * @return string
     */
    public function resolve(LocalConfigPlaceholder $placeholder): string;
}