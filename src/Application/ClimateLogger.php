<?php declare (strict_types = 1);

namespace Shopware\Psh\Application;

use League\CLImate\CLImate;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\Logger;

/**
 * A CLImate implementation of the runtime logger
 */
class ClimateLogger implements Logger
{

    /**
     * @var CLImate
     */
    private $cliMate;

    /**
     * @param CLImate $cliMate
     */
    public function __construct(CLImate $cliMate)
    {
        $this->cliMate = $cliMate;
    }

    /**
     * @param Script $script
     */
    public function logScript(Script $script)
    {
        $this->cliMate->green()->out("<bold>Starting Execution of '" . $script->getName() . "'</bold> <dim>('" . $script->getPath() . "')</dim>\n");
    }

    /**
     * @param string $shellCommand
     * @param int $line
     * @param bool $isIgnoreError
     * @param int $index
     * @param int $max
     */
    public function logCommandStart(string $shellCommand, int $line, bool $isIgnoreError, int $index, int $max)
    {
        $index++;
        $this->cliMate->yellow("\n({$index}/{$max}) Starting\n<bold>> {$shellCommand}</bold>");
    }

    /**
     * @param string $response
     */
    public function err(string $response)
    {
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            $this->cliMate->red("\t$line");
        }
    }

    /**
     * @param string $response
     */
    public function out(string $response)
    {
        $lines = explode(PHP_EOL, $response);

        foreach ($lines as $rowRaw) {
            $row = trim($rowRaw);

            if (!$row) {
                continue;
            }

            $this->cliMate->green("\t" . $row);
        }
    }
}
