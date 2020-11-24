<?php declare(strict_types=1);

namespace Shopware\Psh\Listing;

use function getmypid;
use function mb_strpos;
use function pathinfo;

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
    private $isHiddenPath;

    /**
     * @var string
     */
    private $workingDirectory;

    public function __construct(
        string $directory,
        string $scriptName,
        bool $isHiddenPath,
        string $workingDirectory,
        ?string $environment = null,
        string $description = ''
    ) {
        $this->directory = $directory;
        $this->scriptName = $scriptName;
        $this->environment = $environment;
        $this->description = $description;
        $this->isHiddenPath = $isHiddenPath;
        $this->workingDirectory = $workingDirectory;
    }

    public function getTmpPath(): string
    {
        return $this->directory . '/.tmp_' . getmypid() . '_' . $this->scriptName;
    }

    public function getPath(): string
    {
        return $this->directory . '/' . $this->scriptName;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isHidden(): bool
    {
        return $this->isHiddenPath || mb_strpos($this->scriptName, '.') === 0;
    }

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }
}
