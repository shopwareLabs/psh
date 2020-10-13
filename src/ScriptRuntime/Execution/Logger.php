<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use Shopware\Psh\Listing\Script;

/**
 * Since the script execution must produce live output this removes a direct dependency and enables testability of command execution
 */
interface Logger
{
    public function startScript(Script $script);

    public function finishScript(Script $script);

    public function logStart(string $headline, string $subject, int $line, bool $isIgnoreError, int $index, int $max);

    /**
     * @return void
     */
    public function logWait();

    public function log(LogMessage $logMessage);

    public function logSuccess();

    public function logFailure();

    public function warn(string $message);
}
