<?php declare (strict_types = 1);

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
    private $dynamicVariables;
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
     * @param array $dynamicVariables
     * @param array $constants
     */
    public function __construct(
        string $header = null,
        array $commandPaths,
        array $dynamicVariables,
        array $constants
    ) {
        $this->commandPaths = $commandPaths;
        $this->dynamicVariables = $dynamicVariables;
        $this->constants = $constants;
        $this->header = $header;
    }

    /**
     * @return array
     */
    public function getScriptPaths()
    {
        return $this->commandPaths;
    }

    /**
     * @return array
     */
    public function getDynamicVariables()
    {
        return $this->dynamicVariables;
    }

    /**
     * @return array
     */
    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }
}
