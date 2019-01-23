<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

class WaitCommand implements Command
{
    /**
     * @var int
     */
    private $lineNumber;

    /**
     */
    public function __construct(
        int $lineNumber
    ) {
        $this->lineNumber = $lineNumber;
    }


    /**
     * @return int
     */
    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }
}
