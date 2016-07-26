<?php declare (strict_types = 1);


namespace Shopware\Psh\ScriptRuntime;

class Template
{
    /**
     * @var string
     */
    private $source;
    /**
     * @var string
     */
    private $destination;

    public function __construct(string $source, string $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    public function getContent(): string
    {
        if (!file_exists($this->source)) {
            throw new \InvalidArgumentException('File source not found in "' . $this->source . '"');
        }

        return file_get_contents($this->source);
    }

    public function setContents(string $contents)
    {
        file_put_contents($this->destination, $contents);
    }
}
