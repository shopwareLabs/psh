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
     * @var ScriptsPath[]
     */
    private $scriptPaths;

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
    private $requiredVariables;

    /**
     * @var array
     */
    private $imports;

    public function __construct(
        bool $hidden,
        array $scriptPaths = [],
        array $dynamicVariables = [],
        array $constants = [],
        array $templates = [],
        array $dotenvPaths = [],
        array $requiredVariables = [],
        array $imports = []
    ) {
        $this->hidden = $hidden;
        $this->scriptPaths = $scriptPaths;
        $this->dynamicVariables = $dynamicVariables;
        $this->constants = $constants;
        $this->templates = $templates;
        $this->dotenvPaths = $dotenvPaths;
        $this->requiredVariables = $requiredVariables;
        $this->imports = $imports;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function getAllScriptsPaths(): array
    {
        return $this->scriptPaths;
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

    public function getRequiredVariables(): array
    {
        return $this->requiredVariables;
    }

    public function getImports(): array
    {
        return $this->imports;
    }
}
