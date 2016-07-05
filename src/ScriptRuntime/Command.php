<?php declare(strict_types = 1);


namespace Shopware\Psh\ScriptRuntime;


class Command
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

    public function __construct(string $shellCommand, int $lineNumber, bool $ignoreError)
    {
        $this->shellCommand = $shellCommand;
        $this->ignoreError = $ignoreError;
        $this->lineNumber = $lineNumber;
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
}