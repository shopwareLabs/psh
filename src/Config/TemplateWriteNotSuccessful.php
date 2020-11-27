<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use RuntimeException;
use Shopware\Psh\PshErrorMessage;
use Throwable;
use function sprintf;

/** @psalm-immutable */
class TemplateWriteNotSuccessful extends RuntimeException implements PshErrorMessage
{
    public function __construct(string $path, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Could not write to "%s"', $path), 0, $previous);
    }
}
