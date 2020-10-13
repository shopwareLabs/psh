<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

class LogMessage
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var bool
     */
    private $error;

    public function __construct(string $message, bool $error)
    {
        $this->message = $message;
        $this->error = $error;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isError(): bool
    {
        return $this->error;
    }
}
