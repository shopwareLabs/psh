<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use ReflectionClass;

class ApplicationOptions
{
    public const FLAG_NO_HEADER = '--no-header';

    public static function getAllFlags(): array
    {
        return (new ReflectionClass(self::class))->getConstants();
    }
}
