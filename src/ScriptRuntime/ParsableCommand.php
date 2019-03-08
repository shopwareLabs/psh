<?php

namespace Shopware\Psh\ScriptRuntime;

/**
 * A single command of a script
 */
interface ParsableCommand extends Command
{
    /**
     * @return string
     */
    public function getShellCommand(): string;
}
