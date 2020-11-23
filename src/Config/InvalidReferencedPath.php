<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Exception;
use Shopware\Psh\PshErrorMessage;
use Throwable;
use function print_r;
use function sprintf;

/**
 * @psalm-immutable
 */
class InvalidReferencedPath extends Exception implements PshErrorMessage
{
    public function __construct(string $absoluteOrRelativePath, array $possiblyValidFiles, ?Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Unable to find a file referenced by "%s", tried: %s',
            $absoluteOrRelativePath,
            print_r($possiblyValidFiles, true)
        ), 0, $previous);
    }
}
