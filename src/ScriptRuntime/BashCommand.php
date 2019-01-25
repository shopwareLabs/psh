<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

use Shopware\Psh\Listing\Script;

class BashCommand implements Command
{
    /**
     * @var Script
     */
    private $script;

    /**
     * @param Script $script
     */
    public function __construct(Script $script)
    {
        $this->script = $script;
    }

    /**
     * @return Script
     */
    public function getScript(): Script
    {
        return $this->script;
    }

    /**
     * @return int
     */
    public function getLineNumber(): int
    {
        return 1;
    }
}
