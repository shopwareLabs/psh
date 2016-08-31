<?php declare(strict_types=1);


namespace Shopware\Psh\ScriptRuntime;

use Shopware\Psh\Listing\Script;

/**
 * Load scripts and parse it into commands
 */
class ScriptLoader
{
    const MODIFIER_IS_TTY = 'TTY: ';

    const MODIFIER_IGNORE_ERROR_PREFIX = 'I: ';

    const INCLUDE_STATEMENT_PREFIX = 'INCLUDE: ';

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

        foreach ($lines as $lineNumber => $currentLine) {
            $ignoreError = false;
            $tty = false;

            if (!$this->isExecutableLine($currentLine)) {
                continue;
            }

            if ($this->startsWith(self::CONCATENATE_PREFIX, $currentLine)) {
                $this->commandBuilder->add($currentLine);
                continue;
            }

            if ($this->startsWith(self::INCLUDE_STATEMENT_PREFIX, $currentLine)) {
                $path = $this->findInclude($script, $this->removeFromStart(self::INCLUDE_STATEMENT_PREFIX, $currentLine));
                $includeScript = new Script(pathinfo($path, PATHINFO_DIRNAME), pathinfo($path, PATHINFO_BASENAME));

                $commands = $this->loadScript($includeScript);
                $this->commandBuilder->setCommands($commands);

                continue;
            }

            if ($this->startsWith(self::MODIFIER_IGNORE_ERROR_PREFIX, $currentLine)) {
                $currentLine = $this->removeFromStart(self::MODIFIER_IGNORE_ERROR_PREFIX, $currentLine);
                $ignoreError = true;
            }

            if ($this->startsWith(self::MODIFIER_IS_TTY, $currentLine)) {
                $currentLine = $this->removeFromStart(self::MODIFIER_IS_TTY, $currentLine);
                $tty = true;
            }

            $this->commandBuilder
                ->next($currentLine, $lineNumber, $ignoreError, $tty);
        }

        return $this->commandBuilder->getAll();
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
        if (!strlen(trim($command))) {
            return false;
        }

        if ($this->startsWith('#', trim($command))) {
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
    protected function loadFileContents(string $file): string
    {
        $contents = file_get_contents($file);

        if (false === $contents) {
            throw new \RuntimeException('Unable to load config data - read failed in "' . $file . '".');
        }

        return $contents;
    }
}
