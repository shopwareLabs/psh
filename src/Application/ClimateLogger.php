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
    public $cliMate;

    /**
     * @var int
     */
    private $scriptStartTime;

    /**
     * @var Duration
     */
    private $duration;

    /**
     * @param CLImate $cliMate
     * @param Duration $duration
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
        $this->cliMate->yellow()->inline("\n({$index}/{$max}) Starting\n<bold>> {$shellCommand}</bold>\n\t");
    }


    /**
     * @param string $destination
     * @param int $line
     * @param int $index
     * @param int $max
     */
    public function logTemplate(string $destination, int $line, int $index, int $max)
    {
        $index++;
        $this->cliMate->yellow()->inline("\n({$index}/{$max}) Rendering\n<bold>> {$destination}</bold>\n\t");
    }

    /**
     * @param string $response
     */
    public function err(string $response)
    {
        $this->cliMate->red()->inline($this->formatOutput($response));
    }

    /**
     * @param string $response
     */
    public function out(string $response)
    {
        $this->cliMate->green()->inline($this->formatOutput($response));
    }

    /**
     * @param string $response
     * @return string
     */
    private function formatOutput(string $response) :string
    {
        return str_replace(PHP_EOL, PHP_EOL . "\t", $response);
    }
}
