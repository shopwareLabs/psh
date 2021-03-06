<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

class RuntimeParameters
{
    /**
     * @var array
     */
    private $appParams;

    /**
     * @var array
     */
    private $overwrites;

    /**
     * @var array
     */
    private $commands;

    public function __construct(
        array $appParams,
        array $commands,
        array $overwrites
    ) {
        $this->appParams = $appParams;
        $this->overwrites = $overwrites;
        $this->commands = $commands;
    }

    public function getAppParams(): array
    {
        return $this->appParams;
    }

    public function getOverwrites(): array
    {
        return $this->overwrites;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }
}
