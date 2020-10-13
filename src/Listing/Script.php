<?php declare (strict_types=1);


namespace Shopware\Psh\Listing;

/**
 * ValueObject containing all information for a single script to be executed
 */
class Script
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $scriptName;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    public $description;

    /**
     * @var bool
     */
    private $inHiddenPath;

    /**
     * @param string $directory
     * @param string $scriptName
     * @param bool $inHiddenPath
     * @param string $environment
     * @param string $description
     */
    public function __construct(
        string $directory,
        string $scriptName,
        bool $inHiddenPath,
        string $environment = null,
        $description = ''
    ) {
        $this->directory = $directory;
        $this->scriptName = $scriptName;
        $this->environment = $environment;
        $this->description = $description;
        $this->inHiddenPath = $inHiddenPath;
    }

    /**
     * @return string
     */
    public function getTmpPath(): string
    {
        return $this->directory . '/.tmp_' . getmypid() . '_' . $this->scriptName;
    }


    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->directory . '/' . $this->scriptName;
    }

    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        $name = pathinfo($this->scriptName, PATHINFO_FILENAME);

        if (!$this->environment) {
            return $name;
        }

        return $this->environment . ':' . $name;
    }

    /**
     * @return string|null
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->inHiddenPath || strpos($this->scriptName, '.') === 0;
    }
}
