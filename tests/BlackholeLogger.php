<?php declare(strict_types=1);

namespace Shopware\Psh\Test;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\Execution\Logger;
use Shopware\Psh\ScriptRuntime\Execution\LogMessage;

class BlackholeLogger implements Logger
{
    public $errors = [];

    public $output = [];

    public function startScript(Script $script)
    {
    }

    public function finishScript(Script $script)
    {
    }

    /**
     * @return void
     */
    public function logWait()
    {
    }

    public function logStart(string $headline, string $subject, int $line, bool $isIgnoreError, int $index, int $max)
    {
    }

    public function log(LogMessage $logMessage)
    {
        if ($logMessage->isError()) {
            $this->errors[] = $logMessage->getMessage();
        } else {
            $this->output[] = $logMessage->getMessage();
        }
    }

    public function logSuccess()
    {
    }

    public function logFailure()
    {
    }

    public function warn(string $message)
    {
        // TODO: Implement warn() method.
    }
}
