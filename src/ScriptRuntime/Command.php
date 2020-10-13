<?php declare (strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

interface Command
{
    /**
     * @return int
     */
    public function getLineNumber(): int;
}
