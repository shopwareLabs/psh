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
     * @var string
     */
    private $warning;

    /**
     * @param Script $script
     * @param string $warning
     */
    public function __construct(Script $script, string $warning = null)
    {
        $this->script = $script;
        $this->warning = $warning;
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

    /**
     * @return bool
     */
    public function hasWarning(): bool
    {
        return null !== $this->warning;
    }

    /**
     * @return string
     */
    public function getWarning(): string
    {
        return $this->warning;
    }
}
