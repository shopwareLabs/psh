<?php declare(strict_types=1);


namespace Shopware\Psh\ScriptRuntime;

/**
 * Again, a statefull builder to ease the parsing
 */
class CommandBuilder
{
    /**
     * @var ProcessCommand[]
     */
    private $allCommands = [];

    /**
     * @var string
     */
    private $currentShellCommand;

    /**
     * @var int
     */
    private $startLine;

    /**
     * @var bool
     */
    private $ignoreError;

    /**
     * @var bool
     */
    private $tty;

    /**
     * @var bool
     */
    private $template;

    public function __construct()
    {
        $this->finishLast();
    }


    public function finishLast(): CommandBuilder
    {
        if ($this->currentShellCommand) {
            $this->allCommands[] = new ProcessCommand(
                $this->currentShellCommand,
                $this->startLine,
                $this->ignoreError,
                $this->tty
            );
        }

        $this->currentShellCommand = null;
        $this->startLine = null;
        $this->ignoreError = false;
        $this->tty = false;
        $this->template = false;

        return $this;
    }

    /**
     * @param string $shellCommand
     * @param int $startLine
     * @param bool $ignoreError
     * @return CommandBuilder
     */
    public function setExecutable(string $shellCommand, int $startLine): CommandBuilder
    {
        $this->currentShellCommand = $shellCommand;
        $this->startLine = $startLine;

        return $this;
    }

    /**
     * @param string $source
     * @param string $destination
     * @param int $lineNumber
     * @return $this
     */
    public function addTemplateCommand(string $source, string $destination, int $lineNumber)
    {
        $this->allCommands[] = new TemplateCommand(
            $source,
            $destination,
            $lineNumber
        );

        return $this;
    }

    /**
     * @param bool $set
     * @return CommandBuilder
     */
    public function setIgnoreError(bool $set = true): CommandBuilder
    {
        $this->ignoreError = $set;

        return $this;
    }

    public function setTty(bool $set = true): CommandBuilder
    {
        $this->tty = $set;

        return $this;
    }

    /**
     * @param string $shellCommand
     * @return CommandBuilder
     */
    public function append(string $shellCommand): CommandBuilder
    {
        $this->currentShellCommand .= ' ' . trim($shellCommand);

        return $this;
    }

    /**
     * @param array $commands
     * @return CommandBuilder
     */
    public function setCommands(array $commands): CommandBuilder
    {
        $this->allCommands = $commands;

        return $this;
    }

    /**
     * @return ProcessCommand[]
     */
    public function getAll(): array
    {
        $allCommands = $this->allCommands;
        $this->allCommands = [];

        return $allCommands;
    }
}
