<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\ScriptLoader;

use RuntimeException;
use Shopware\Psh\PshErrorMessage;

/** @psalm-immutable */
class ScriptNotSupportedByParser extends RuntimeException implements PshErrorMessage
{
}
