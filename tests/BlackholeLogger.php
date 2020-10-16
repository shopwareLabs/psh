<?php declare(strict_types=1);

namespace Shopware\Psh\Test;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\Execution\Logger;
use Shopware\Psh\ScriptRuntime\Execution\LogMessage;

class BlackholeLogger implements Logger
{
    public $errors = [];

    public $output = [];

    public function startScript(Script $script): void
    {
    }

    public function finishScript(Script $script): void
    {
    }

    public function logWait(): void
    {
    }

    public function logStart(string $headline, string $subject, int $line, bool $isIgnoreError, int $index, int $max): void
    {
    }

    public function log(LogMessage $logMessage): void
    {
        if ($logMessage->isError()) {
            $this->errors[] = $logMessage->getMessage();
        } else {
            $this->output[] = $logMessage->getMessage();
        }
    }

    public function logSuccess(): void
    {
    }

    public function logFailure(): void
    {
    }

    public function warn(string $message): void
    {
        // TODO: Implement warn() method.
    }
}
