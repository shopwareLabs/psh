<?php declare(strict_types=1);

namespace Shopware\Psh\Listing;

use RuntimeException;
use Shopware\Psh\PshErrorMessage;

/**
 * Special exception to enable a nice error message from the app if given script path is not valid
 * @psalm-immutable
 */
class ScriptPathNotValid extends RuntimeException implements PshErrorMessage
{
    public function getPshMessage(): string
    {
        return $this->getMessage();
    }
}
