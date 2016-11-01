<?php declare(strict_types=1);

namespace Shopware\Psh\Test;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\Logger;

class BlackholeLogger implements Logger
{
    public $errors = [];
    public $output = [];


    /**
     * @param Script $script
     */
    public function startScript(Script $script)
    {
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
    }

    /**
     * @param string $response
     */
    public function err(string $response)
    {
        $this->errors[] = $response;
    }

    /**
     * @param string $response
     */
    public function out(string $response)
    {
        $this->output[] = $response;
    }

    /**
     * @param Script $script
     */
    public function finishScript(Script $script)
    {
    }
}
