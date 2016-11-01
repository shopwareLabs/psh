<?php declare(strict_types=1);


namespace Shopware\Psh\ScriptRuntime;

use Shopware\Psh\Listing\Script;

/**
 * Since the script execution must produce live output this removes a direct dependency and enables testability of command execution
 */
interface Logger
{
    /**
     * @param Script $script
     */
    public function startScript(Script $script);

    /**
     * @param Script $script
     */
    public function finishScript(Script $script);

    /**
     * @param string $shellCommand
     * @param int $line
     * @param bool $isIgnoreError
     * @param int $index
     * @param int $max
     */
    public function logCommandStart(string $shellCommand, int $line, bool $isIgnoreError, int $index, int $max);

    /**
     * @param string $response
     */
    public function err(string $response);

    /**
     * @param string $response
     */
    public function out(string $response);
}
