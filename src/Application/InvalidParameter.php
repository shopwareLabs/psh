<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use RuntimeException;
use Shopware\Psh\PshErrorMessage;

/**
 * Special exception to enable a nice error message from the app
 *
 * @psalm-immutable
 */
class InvalidParameter extends RuntimeException implements PshErrorMessage
{
    public function getPshMessage(): string
    {
        return $this->getMessage();
    }
}
