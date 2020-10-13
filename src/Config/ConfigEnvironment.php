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

    public function __construct(
        bool $hidden,
        array $commandPaths = [],
        array $dynamicVariables = [],
        array $constants = [],
        array $templates = [],
        array $dotenvPaths = []
    ) {
        $this->hidden = $hidden;
        $this->commandPaths = $commandPaths;
        $this->dynamicVariables = $dynamicVariables;
        $this->constants = $constants;
        $this->templates = $templates;
        $this->dotenvPaths = $dotenvPaths;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function getAllScriptsPaths(): array
    {
        return $this->commandPaths;
    }

    public function getDynamicVariables(): array
    {
        return $this->dynamicVariables;
    }

    public function getConstants(): array
    {
        return $this->constants;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function getDotenvPaths(): array
    {
        return $this->dotenvPaths;
    }
}
