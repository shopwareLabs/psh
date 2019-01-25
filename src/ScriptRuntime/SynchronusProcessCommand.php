<?php declare(strict_types=1);


namespace Shopware\Psh\ScriptRuntime;

/**
 * A single command of a script
 */
class SynchronusProcessCommand implements ProcessCommand
{
    /**
     * @var string
     */
    private $shellCommand;

    /**
     * @var bool
     */
    private $ignoreError;

    /**
     * @var int
     */
    private $lineNumber;

    /**
     * @var bool
     */
    private $tty;

    /**
     * @param string $shellCommand
     * @param int $lineNumber
     * @param bool $ignoreError
     * @param bool $tty
     */
    public function __construct(
        string $shellCommand,
        int $lineNumber,
        bool $ignoreError,
        bool $tty
    ) {
        $this->shellCommand = $shellCommand;
        $this->ignoreError = $ignoreError;
        $this->lineNumber = $lineNumber;
        $this->tty = $tty;
    }

    /**
     * @return string
     */
    public function getShellCommand(): string
    {
        return $this->shellCommand;
    }

    /**
     * @return boolean
     */
    public function isIgnoreError(): bool
    {
        return $this->ignoreError;
    }

    /**
     * @return int
     */
    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    /**
     * @return bool
     */
    public function isTTy(): bool
    {
        return $this->tty;
    }
}
