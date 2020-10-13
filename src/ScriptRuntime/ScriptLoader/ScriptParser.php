<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\ScriptLoader;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\Command;

/**
 * Load scripts and parse it into commands
 */
interface ScriptParser
{
    /**
     * @return Command[]
     */
    public function parseContent(string $content, Script $script, ScriptLoader $loader): array;
}
