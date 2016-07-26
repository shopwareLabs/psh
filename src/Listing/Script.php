<?php declare (strict_types = 1);


namespace Shopware\Psh\Listing;

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
     * @param string $directory
     * @param string $scriptName
     * @param string $environment
     */
    public function __construct(string $directory, string $scriptName, string $environment = null)
    {
        $this->directory = $directory;
        $this->scriptName = $scriptName;
        $this->environment = $environment;
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
}
