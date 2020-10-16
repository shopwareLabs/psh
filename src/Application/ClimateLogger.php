<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use Khill\Duration\Duration;
use League\CLImate\CLImate;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\Execution\Logger;
use Shopware\Psh\ScriptRuntime\Execution\LogMessage;
use function sprintf;
use function str_replace;
use function time;

/**
 * A CLImate implementation of the runtime logger
 */
class ClimateLogger implements Logger
{
    const WARNING_TEMPLATE = <<<EOD
<yellow>
##############################################################################################################
               <bold>WARNING</bold>
  %s

##############################################################################################################
</yellow>
EOD;

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

    public function __construct(CLImate $cliMate, Duration $duration)
    {
        $this->cliMate = $cliMate;
        $this->duration = $duration;
    }

    public function startScript(Script $script): void
    {
        $this->scriptStartTime = time();
        $this->cliMate->green()->out("<bold>Starting Execution of '" . $script->getName() . "'</bold> <dim>('" . $script->getPath() . "')</dim>\n");
    }

    public function finishScript(Script $script): void
    {
        $durationInSeconds = time() - $this->scriptStartTime;

        if (!$durationInSeconds) {
            $durationInSeconds = 1;
        }

        $this->cliMate->green()->out("\n<bold>Duration: " . $this->duration->humanize($durationInSeconds) . '</bold>');
    }

    private function formatOutput(string $response): string
    {
        return str_replace(PHP_EOL, PHP_EOL . "\t", $response);
    }

    public function logWait(): void
    {
        $this->cliMate->green()->bold()->inline("\nWAITING...\n\t");
    }

    public function log(LogMessage $logMessage): void
    {
        if ($logMessage->isError()) {
            $this->err($logMessage->getMessage());
        } else {
            $this->out($logMessage->getMessage());
        }
    }

    private function err(string $response): void
    {
        $this->cliMate->red()->inline($this->formatOutput($response));
    }

    private function out(string $response): void
    {
        $this->cliMate->green()->inline($this->formatOutput($response));
    }

    public function logStart(string $headline, string $subject, int $line, bool $isIgnoreError, int $index, int $max): void
    {
        $index++;
        $this->cliMate->yellow()->inline("\n({$index}/{$max}) $headline\n<bold>> {$subject}</bold>\n\t");
    }

    public function logSuccess(): void
    {
        $this->cliMate->green()->bold()->out('Executed Successfully');
    }

    public function logFailure(): void
    {
        $this->cliMate->green()->red()->out('Executed with failure');
    }

    public function warn(string $message): void
    {
        $this->cliMate->out(sprintf(self::WARNING_TEMPLATE, $message));
    }
}
