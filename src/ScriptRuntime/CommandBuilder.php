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

    /**
     * @var bool
     */
    private $deferred;

    public function __construct()
    {
        $this->reset();
    }

    private function reset()
    {
        $this->ignoreError = false;
        $this->tty = false;
        $this->template = false;
        $this->deferred = false;
    }

    /**
     * @param string $shellCommand
     * @param int $startLine
     * @return CommandBuilder
     */
    public function addProcessCommand(string $shellCommand, int $startLine): CommandBuilder
    {
        $this->allCommands[] = new ProcessCommand(
            $shellCommand,
            $startLine,
            $this->ignoreError,
            $this->tty,
            $this->deferred
        );

        $this->reset();

        return $this;
    }

    /**
     * @param string $source
     * @param string $destination
     * @param int $lineNumber
     * @return $this
     */
    public function addTemplateCommand(string $source, string $destination, int $lineNumber): CommandBuilder
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
     * @param int $lineNumber
     * @return CommandBuilder
     */
    public function addWaitCommand(int $lineNumber): CommandBuilder
    {
        $this->reset();
        $this->allCommands[] = new WaitCommand($lineNumber);

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
     * @param bool $set
     * @return CommandBuilder
     */
    public function setTty(bool $set = true): CommandBuilder
    {
        $this->tty = $set;

        return $this;
    }

    /**
     * @param bool $set
     * @return CommandBuilder
     */
    public function setDeferredExecution(bool $set = true): CommandBuilder
    {
        $this->deferred = $set;

        return $this;
    }

    /**
     * @param array $commands
     * @return CommandBuilder
     */
    public function replaceCommands(array $commands): CommandBuilder
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
