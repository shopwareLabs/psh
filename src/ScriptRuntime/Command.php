<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

interface Command
{
    public function getLineNumber(): int;
}
