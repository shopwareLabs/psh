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
    /**
     * @var array
     */
    private $templates;

    /**
     * ConfigEnvironment constructor.
     * @param array $commandPaths
     * @param array $dynamicVariables
     * @param array $constants
     * @param array $templates
     */
    public function __construct(
        array $commandPaths = [],
        array $dynamicVariables = [],
        array $constants = [],
        array $templates = []
    ) {
        $this->commandPaths = $commandPaths;
        $this->dynamicVariables = $dynamicVariables;
        $this->constants = $constants;
        $this->templates = $templates;
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
    public function getDynamicVariables(): array
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

    public function getTemplates(): array
    {
        return $this->templates;
    }
}
