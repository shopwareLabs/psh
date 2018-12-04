<?php declare(strict_types=1);


namespace Shopware\Psh\ScriptRuntime;

use Shopware\Psh\Listing\Script;

/**
 * Load scripts and parse it into commands
 */
class ScriptParser
{
    const MODIFIER_IS_TTY = 'TTY: ';

    const MODIFIER_IGNORE_ERROR_PREFIX = 'I: ';

    const INCLUDE_STATEMENT_PREFIX = 'INCLUDE: ';

    const TEMPLATE_STATEMENT_PREFIX = 'TEMPLATE: ';

    const CONCATENATE_PREFIX = '   ';

    /**
     * @var CommandBuilder
     */
    private $commandBuilder;

    /**
     * @param CommandBuilder $commandBuilder
     */
    public function __construct(CommandBuilder $commandBuilder)
    {
        $this->commandBuilder = $commandBuilder;
    }

    /**
     * @param Script $script
     * @return array
     */
    public function loadScript(Script $script): array
    {
        $content = $this->loadFileContents($script->getPath());
        $lines = explode("\n", $content);

        $lines = $this->concatenateStatements($lines);

        foreach ($lines as $lineNumber => $currentLine) {
            if ($this->isNotHandledAsCommand($script, $lineNumber, $currentLine)) {
                continue;
            }

            $this->commandBuilder->finishLast();

            if ($this->startsWith(self::MODIFIER_IGNORE_ERROR_PREFIX, $currentLine)) {
                $currentLine = $this->removeFromStart(self::MODIFIER_IGNORE_ERROR_PREFIX, $currentLine);
                $this->commandBuilder->setIgnoreError();
            }

            if ($this->startsWith(self::MODIFIER_IS_TTY, $currentLine)) {
                $currentLine = $this->removeFromStart(self::MODIFIER_IS_TTY, $currentLine);
                $this->commandBuilder->setTty();
            }

            $this->commandBuilder
                ->setExecutable($currentLine, $lineNumber);
        }

        return $this->commandBuilder->finishLast()
            ->getAll();
    }

    /**
     * @param Script $script
     * @param int $lineNumber
     * @param string $currentLine
     * @return bool
     */
    private function isNotHandledAsCommand(Script $script, int $lineNumber, string $currentLine): bool
    {
        if (!$this->isExecutableLine($currentLine)) {
            return true;
        }

        if ($this->startsWith(self::INCLUDE_STATEMENT_PREFIX, $currentLine)) {
            $path = $this->findInclude($script, $this->removeFromStart(self::INCLUDE_STATEMENT_PREFIX, $currentLine));
            $includeScript = new Script(pathinfo($path, PATHINFO_DIRNAME), pathinfo($path, PATHINFO_BASENAME));

            $commands = $this->loadScript($includeScript);
            $this->commandBuilder->setCommands($commands);

            return true;
        }

        if ($this->startsWith(self::TEMPLATE_STATEMENT_PREFIX, $currentLine)) {
            $definition = $this->removeFromStart(self::TEMPLATE_STATEMENT_PREFIX, $currentLine);
            list($rawSource, $rawDestination) = explode(':', $definition);

            $source = $script->getDirectory() . '/' . $rawSource;
            $destination = $script->getDirectory() . '/' . $rawDestination;

            $this->commandBuilder
                ->finishLast()
                ->addTemplateCommand($source, $destination, $lineNumber);

            return true;
        }

        return false;
    }

    /**
     * @param Script $fromScript
     * @param string $includeStatement
     * @return string
     */
    private function findInclude(Script $fromScript, string $includeStatement): string
    {
        if (file_exists($includeStatement)) {
            return $includeStatement;
        }

        if (file_exists($fromScript->getDirectory() . '/' . $includeStatement)) {
            return $fromScript->getDirectory() . '/' . $includeStatement;
        }

        throw new \RuntimeException('Unable to parse include statement "' . $includeStatement . '" in "' . $fromScript->getPath() . '"');
    }

    /**
     * @param string $command
     * @return bool
     */
    private function isExecutableLine(string $command): bool
    {
        $command = trim($command);

        if (!$command) {
            return false;
        }

        if ($this->startsWith('#', $command)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @return string
     */
    private function removeFromStart(string $needle, string $haystack): string
    {
        return substr($haystack, strlen($needle));
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @return bool
     */
    private function startsWith(string $needle, string $haystack): bool
    {
        return strpos($haystack, $needle) === 0;
    }

    /**
     * @param string $file
     * @return string
     */
    private function loadFileContents(string $file): string
    {
        return file_get_contents($file);
    }

    /**
     * @param array $lines
     * @return array
     */
    private function concatenateStatements(array $lines): array
    {
        $filteredResult = [];
        $previousLine = key($lines);

        foreach ($lines as $lineNumber => $currentLine) {
            if ($this->startsWith(self::CONCATENATE_PREFIX, $currentLine)) {
                $filteredResult[$previousLine] .= ' ' . trim($currentLine);
                continue;
            }

            $filteredResult[$lineNumber] = $currentLine;
            $previousLine = $lineNumber;
        }

        return $filteredResult;
    }
}
