<?php declare (strict_types=1);


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

    private function reset()
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
    }

    /**
     * @param string $shellCommand
     * @param int $startLine
     * @param bool $ignoreError
     * @return CommandBuilder
     */
    public function next(string $shellCommand, int $startLine, bool $ignoreError, bool $tty): CommandBuilder
    {
        $this->reset();

        $this->currentShellCommand = $shellCommand;
        $this->startLine = $startLine;
        $this->ignoreError = $ignoreError;
        $this->tty = $tty;

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
        $this->reset();

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

    /**
     * @param string $shellCommand
     * @return CommandBuilder
     */
    public function add(string $shellCommand): CommandBuilder
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
        $this->reset();
        $allCommands = $this->allCommands;
        $this->allCommands = [];

        return $allCommands;
    }
}
