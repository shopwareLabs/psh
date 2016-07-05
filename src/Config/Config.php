<?php declare(strict_types = 1);

namespace Shopware\Psh\Config;

class Config
{
    /**
     * @var array
     */
    private $commandPaths;
    /**
     * @var array
     */
    private $environmentVariable;
    /**
     * @var array
     */
    private $constants;
    /**
     * @var string
     */
    private $header;

    /**
     * Config constructor.
     * @param string $header
     * @param array $commandPaths
     * @param array $environmentVariable
     * @param array $constants
     */
    public function __construct(
        string $header = null,
        array $commandPaths,
        array $environmentVariable,
        array $constants
    ) {
        $this->commandPaths = $commandPaths;
        $this->environmentVariable = $environmentVariable;
        $this->constants = $constants;
        $this->header = $header;
    }

}