<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use Khill\Duration\Duration;
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
     * @var \DateTime
     */
    private $scriptStartTime;
    /**
     * @var int
     */
    private $duration;

    /**
     * @param CLImate $cliMate
     */
    public function __construct(CLImate $cliMate, Duration $duration)
    {
        $this->cliMate = $cliMate;
        $this->duration = $duration;
    }

    /**
     * @param Script $script
     */
    public function startScript(Script $script)
    {
        $this->scriptStartTime = time();
        $this->cliMate->green()->out("<bold>Starting Execution of '" . $script->getName() . "'</bold> <dim>('" . $script->getPath() . "')</dim>\n");
    }

    /**
     * @param Script $script
     */
    public function finishScript(Script $script)
    {
        $durationInSeconds = time() - $this->scriptStartTime;

        if (!$durationInSeconds) {
            $durationInSeconds = 1;
        }

        $this->cliMate->green()->out("\n<bold>Duration: " . $this->duration->humanize($durationInSeconds) . "</bold>");
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
