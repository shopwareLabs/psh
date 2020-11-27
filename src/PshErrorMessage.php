<?php declare(strict_types=1);

namespace Shopware\Psh;

use Throwable;

/** @psalm-immutable */
interface PshErrorMessage extends Throwable
{
    // Incompatible signature in php 7.2 til 8.0
//    public function getPshMessage(): string;
}
