<?php declare (strict_types = 1);


namespace Shopware\Psh\Config;

class ConfigEnvironment
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

    public function __construct(
        array $commandPaths = [],
        array $dynamicVariables = [],
        array $constants = []
    ) {
        $this->commandPaths = $commandPaths;
        $this->dynamicVariables = $dynamicVariables;
        $this->constants = $constants;
    }

    /**
     * @return array
     */
    public function getAllScriptPaths(): array
    {
        return $this->commandPaths;
    }

    /**
     * @return array
     */
    public function getDynamicVariables(string $environment = null): array
    {
        return $this->dynamicVariables;
    }

    /**
     * @return array
     */
    public function getConstants(): array
    {
        return $this->constants;
    }
}
