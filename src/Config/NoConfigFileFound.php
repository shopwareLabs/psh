<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Exception;
use Shopware\Psh\PshErrorMessage;
use Throwable;

/** @psalm-immutable */
class NoConfigFileFound extends Exception implements PshErrorMessage
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('No config file found, make sure you have created a .psh.xml or .psh.xml.dist file', 0, $previous);
    }

    public function getPshMessage(): string
    {
        return $this->getMessage();
    }
}
