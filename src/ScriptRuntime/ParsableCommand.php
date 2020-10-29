<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

/**
 * A single command of a script
 */
interface ParsableCommand extends Command
{
    public function getShellCommand(): string;
}
