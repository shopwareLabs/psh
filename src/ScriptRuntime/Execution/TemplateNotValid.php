<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use RuntimeException;
use Shopware\Psh\PshErrorMessage;

/**
 * A custom exception to enable nice error display
 *
 * @psalm-immutable
 */
class TemplateNotValid extends RuntimeException implements PshErrorMessage
{
    public function getPshMessage(): string
    {
        return $this->getMessage();
    }
}
