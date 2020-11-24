<?php declare(strict_types=1);

namespace Shopware\Psh;

use Throwable;

/** @psalm-immutable */
interface PshErrorMessage extends Throwable
{
    public function getPshMessage(): string;
}
