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
     * @var string|null
     */
    private $warning;

    public function __construct(Script $script, string $warning = null)
    {
        $this->script = $script;
        $this->warning = $warning;
    }

    public function getScript(): Script
    {
        return $this->script;
    }

    public function getLineNumber(): int
    {
        return 1;
    }

    public function isIgnoreError(): bool
    {
        return false;
    }

    public function isTTy(): bool
    {
        return false;
    }

    public function hasWarning(): bool
    {
        return null !== $this->warning;
    }

    public function getWarning(): string
    {
        return $this->warning;
    }
}
