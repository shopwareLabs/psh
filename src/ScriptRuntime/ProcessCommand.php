<?php

namespace Shopware\Psh\ScriptRuntime;

/**
 * A single command of a script
 */
interface ProcessCommand extends Command
{
    /**
     * @return boolean
     */
    public function isIgnoreError(): bool;

    /**
     * @return int
     */
    public function getLineNumber(): int;

    /**
     * @return bool
     */
    public function isTTy(): bool;
}
