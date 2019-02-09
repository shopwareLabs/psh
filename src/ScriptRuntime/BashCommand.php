<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

use Shopware\Psh\Listing\Script;

class BashCommand implements ProcessCommand
{
    /**
     * @var Script
     */
    private $script;

    /**
     * @var string[]
     */
    private $warnings;

    /**
     * @param Script $script
     * @param string ...$warnings
     */
    public function __construct(Script $script, string ...$warnings)
    {
        $this->script = $script;
        $this->warnings = $warnings;
    }

    /**
     * @return Script
     */
    public function getScript(): Script
    {
        return $this->script;
    }

    /**
     * @return int
     */
    public function getLineNumber(): int
    {
        return 1;
    }

    /**
     * @return boolean
     */
    public function isIgnoreError(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isTTy(): bool
    {
        return false;
    }
}
