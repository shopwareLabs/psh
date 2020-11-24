<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

/**
 * A single command of a script
 */
interface ProcessCommand extends Command
{
    public function isIgnoreError(): bool;

    public function getLineNumber(): int;

    public function isTTy(): bool;

    public function getWorkingDirectory(): string;
}
