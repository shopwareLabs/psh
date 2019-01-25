<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\ScriptLoader;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\BashCommand;

class BashScriptParser implements ScriptParser
{
    const TYPE_DIRECT_EXECUTE = '<PSH_EXECUTE_THROUGH_CMD>';

    /**
     * @todo add validation and notice
     */
    const SHOULD_BE_PRESENT = "\nset -euo pipefail\n";

    /**
     * {@inheritdoc}
     */
    public function parseContent(string $content, Script $script): array
    {
        if (strpos($content, self::TYPE_DIRECT_EXECUTE) === false) {
            throw new ScriptNotSupportedByParser('Marker for execution missing');
        }

        if (!is_executable($script->getPath())) {
            throw new \RuntimeException('Bash scripts can only be executed if they are executable please execute chmod + ' . $script->getPath());
        }

        return [new BashCommand($script)];
    }
}
