<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use League\CLImate\CLImate;
use Shopware\Psh\Config\ConfigLogger;
use function sprintf;
use function str_replace;

class ApplicationConfigLogger implements ConfigLogger
{
    /**
     * @var string
     */
    private $rootDirectory;

    private $output = [];

    public function __construct(string $rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
    }

    public function mainConfigFiles(string $mainFile, string $overrideFile = null): void
    {
        if ($overrideFile === null) {
            $this->print(sprintf('Using %s', $this->cleanUpPath($mainFile)));

            return;
        }

        $this->print(sprintf(
            'Using %s extended by %s',
            $this->cleanUpPath($mainFile),
            $this->cleanUpPath($overrideFile)
        ));
    }

    public function notifyImportNotFound(string $import): void
    {
        $this->print(sprintf(' -> NOTICE: No import found for path "%s"', $import));
    }

    public function importConfigFiles(string $import, string $mainFile, string $overrideFile = null): void
    {
        if ($overrideFile === null) {
            $this->print(sprintf(' -> Importing %s from "%s" ', $this->cleanUpPath($mainFile), $import));

            return;
        }

        $this->print(sprintf(
            ' -> Importing %s extended by %s from "%s"',
            $this->cleanUpPath($mainFile),
            $this->cleanUpPath($overrideFile),
            $import
        ));
    }

    private function cleanUpPath(string $configFile): string
    {
        return str_replace($this->rootDirectory . '/', '', $configFile);
    }

    private function print(string $message): void
    {
        $this->output[] = $message;
    }

    public function printOut(CLImate $cliMate): void
    {
        foreach ($this->output as $message) {
            $cliMate->yellow()->out($message);
        }
        $cliMate->out(PHP_EOL);

        $this->output = [];
    }
}
