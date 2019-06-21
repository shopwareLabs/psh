<?php declare(strict_types=1);


namespace Shopware\Psh\Config;

/**
 * A single configuration environment
 */
class ConfigEnvironment
{
    /**
     * @var bool
     */
    private $hidden;

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
     * @var array
     */
    private $dotenvPaths;
    /**
     * @var array
     */
    private $placeholdersInProcessEnvironment;

    /**
     * @param array $commandPaths
     * @param array $dynamicVariables
     * @param array $constants
     * @param array $templates
     * @param array $dotenvPaths
     * @param bool $hidden
     */
    public function __construct(
        bool $hidden,
        array $commandPaths = [],
        array $dynamicVariables = [],
        array $constants = [],
        array $templates = [],
        array $dotenvPaths = [],
        array $placeholdersInProcessEnvironment = []
    ) {
        $this->hidden = $hidden;
        $this->commandPaths = $commandPaths;
        $this->dynamicVariables = $dynamicVariables;
        $this->constants = $constants;
        $this->templates = $templates;
        $this->dotenvPaths = $dotenvPaths;
        $this->placeholdersInProcessEnvironment = $placeholdersInProcessEnvironment;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @return array
     */
    public function getAllScriptsPaths(): array
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

    /**
     * @return array
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @return array
     */
    public function getDotenvPaths(): array
    {
        return $this->dotenvPaths;
    }
}
