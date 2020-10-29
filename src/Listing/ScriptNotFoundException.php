<?php declare(strict_types=1);

namespace Shopware\Psh\Listing;

use RuntimeException;

/**
 * Special exception to enable a nice error message from the app
 */
class ScriptNotFoundException extends RuntimeException
{
    /**
     * @var string
     */
    private $scriptName;

    public function setScriptName(string $scriptName): self
    {
        $this->scriptName = $scriptName;

        return $this;
    }

    public function getScriptName(): string
    {
        return $this->scriptName;
    }
}
