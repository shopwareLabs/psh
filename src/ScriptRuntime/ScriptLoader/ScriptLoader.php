<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\ScriptLoader;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\Command;
use function file_get_contents;

/**
 * Load scripts and parse it into commands
 */
class ScriptLoader
{
    /**
     * @var ScriptParser[]
     */
    private $parsers;

    /**
     * @param ScriptParser ...$parsers
     */
    public function __construct(ScriptParser ...$parsers)
    {
        $this->parsers = $parsers;
    }

    /**
     * @return Command[]
     */
    public function loadScript(Script $script): array
    {
        $content = $this->loadFileContents($script->getPath());

        foreach ($this->parsers as $parser) {
            try {
                return $parser->parseContent($content, $script, $this);
            } catch (ScriptNotSupportedByParser $e) {
                //nth
            }
        }

        throw new ScriptNotSupportedByParser('Can not find a parser able to parse ' . $script->getPath());
    }

    protected function loadFileContents(string $file): string
    {
        return file_get_contents($file);
    }
}
