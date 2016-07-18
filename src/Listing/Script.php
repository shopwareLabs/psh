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
     * @param string $directory
     * @param string $scriptName
     */
    public function __construct(string $directory, string $scriptName)
    {
        $this->directory = $directory;
        $this->scriptName = $scriptName;
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
        return pathinfo($this->scriptName, PATHINFO_FILENAME);
    }
}
