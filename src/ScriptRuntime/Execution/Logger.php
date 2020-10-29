<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use Shopware\Psh\Listing\Script;

/**
 * Since the script execution must produce live output this removes a direct dependency and enables testability of command execution
 */
interface Logger
{
    public function startScript(Script $script): void;

    public function finishScript(Script $script): void;

    public function logStart(string $headline, string $subject, int $line, bool $isIgnoreError, int $index, int $max): void;

    public function logWait(): void;

    public function log(LogMessage $logMessage): void;

    public function logSuccess(): void;

    public function logFailure(): void;

    public function warn(string $message): void;
}
