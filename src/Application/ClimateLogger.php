<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use Khill\Duration\Duration;
use League\CLImate\CLImate;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\Logger;
use Shopware\Psh\ScriptRuntime\LogMessage;

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
     * @param string $response
     * @return string
     */
    private function formatOutput(string $response) :string
    {
        return str_replace(PHP_EOL, PHP_EOL . "\t", $response);
    }

    /**
     * @return void
     */
    public function logWait()
    {
        $this->cliMate->green()->bold()->inline("\nWAITING...\n\t");
    }

    public function log(LogMessage $logMessage)
    {
        if ($logMessage->isError()) {
            $this->err($logMessage->getMessage());
        } else {
            $this->out($logMessage->getMessage());
        }
    }

    /**
     * @param string $response
     */
    private function err(string $response)
    {
        $this->cliMate->red()->inline($this->formatOutput($response));
    }

    /**
     * @param string $response
     */
    private function out(string $response)
    {
        $this->cliMate->green()->inline($this->formatOutput($response));
    }

    /**
     * @param string $headline
     * @param string $subject
     * @param int $line
     * @param bool $isIgnoreError
     * @param int $index
     * @param int $max
     */
    public function logStart(string $headline, string $subject, int $line, bool $isIgnoreError, int $index, int $max)
    {
        $index++;
        $this->cliMate->yellow()->inline("\n({$index}/{$max}) $headline\n<bold>> {$subject}</bold>\n\t");
    }

    public function logSuccess()
    {
        $this->cliMate->green()->bold()->out('Executed Successfully');
    }

    public function LogFailure()
    {
        $this->cliMate->green()->red()->out('Executed with failure');
    }
}
