<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\ScriptLoader;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\BashCommand;

class BashScriptParser implements ScriptParser
{
    const TYPE_DIRECT_EXECUTE = '<PSH_EXECUTE_THROUGH_CMD>';

    const SHOULD_BE_PRESENT = "set -euo pipefail";

    /**
     * {@inheritdoc}
     */
    public function parseContent(string $content, Script $script, ScriptLoader $loader): array
    {
        $this->testContentContainsMarker($content);
        $this->testScriptFileFitsRequirements($script);

        $shouldWarn = $this->shouldWarnAboutBestPractice($content);
        $warning = null;

        if ($shouldWarn) {
            $warning = 'Execution of this script is not secure, please consider adding <bold>set -euo pipefail</bold> in the beginning';
        }

        return [new BashCommand($script, $warning)];
    }

    /**
     * @param Script $script
     */
    private function testScriptFileFitsRequirements(Script $script)
    {
        if (!is_executable($script->getPath())) {
            throw new \RuntimeException('Bash scripts can only be executed if they are executable please execute <bold>chmod +x ' . $script->getPath() . '</bold>');
        }

        if (!is_writable($script->getDirectory())) {
            throw new \RuntimeException('Bash scripts can only be executed if they are in a writable directory please execute <bold>chmod +w ' . $script->getDirectory() . '</bold>');
        }
    }

    /**
     * @param string $content
     */
    private function testContentContainsMarker(string $content)
    {
        if (strpos($content, self::TYPE_DIRECT_EXECUTE) === false) {
            throw new ScriptNotSupportedByParser('Marker for execution missing');
        }
    }

    /**
     * @param string $content
     * @return bool
     */
    private function shouldWarnAboutBestPractice(string $content): bool
    {
        $firstLines = explode("\n", $content, 5);
        foreach ($firstLines as $line) {
            if ($line === self::SHOULD_BE_PRESENT) {
                return false;
            }
        }

        return true;
    }
}
