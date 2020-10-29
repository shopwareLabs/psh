<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use RuntimeException;
use Throwable;

class ExitSignal extends RuntimeException
{
    public const RESULT_SUCCESS = 0;

    public const RESULT_ERROR = 1;

    /**
     * @var int
     */
    private $signal;

    public static function success(): self
    {
        return new self(self::RESULT_SUCCESS);
    }

    public static function error(): self
    {
        return new self(self::RESULT_ERROR);
    }

    public function __construct(int $signal, $message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->signal = $signal;
    }

    public function signal(): int
    {
        return $this->signal;
    }
}
