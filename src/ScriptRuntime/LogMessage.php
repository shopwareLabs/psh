<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

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

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return $this->error;
    }
}
