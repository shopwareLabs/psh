<?php declare (strict_types=1);


namespace Shopware\Psh\Listing;

/**
 * Special exception to enable a nice error message from the app
 */
class ScriptNotFoundException extends \RuntimeException
{
    /**
     * @var string
     */
    private $scriptName;

    /**
     * @param string $scriptName
     *
     * @return self
     */
    public function setScriptName(string $scriptName): self
    {
        $this->scriptName = $scriptName;

        return $this;
    }

    /**
     * @return string
     */
    public function getScriptName(): string
    {
        return $this->scriptName;
    }
}
