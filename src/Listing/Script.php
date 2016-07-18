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
    private $namespace;

    /**
     * @param string $directory
     * @param string $scriptName
     * @param string $namespace
     */
    public function __construct(string $directory, string $scriptName, string $namespace = null)
    {
        $this->directory = $directory;
        $this->scriptName = $scriptName;
        $this->namespace = $namespace;
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

        if (!$this->namespace) {
            return $name;
        }

        return $this->namespace . ':' . $name;
    }
}
