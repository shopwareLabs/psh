<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use Exception;
use Shopware\Psh\PshErrorMessage;
use Throwable;
use function sprintf;

/** @psalm-immutable */
class MissingRequiredParameter extends Exception implements PshErrorMessage
{
    public function __construct(string $paramName = '', ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Missing required value for "%s"', $paramName), 0, $previous);
    }

    public function getPshMessage(): string
    {
        return $this->getMessage();
    }
}
