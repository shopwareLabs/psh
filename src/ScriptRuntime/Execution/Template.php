<?php declare (strict_types=1);


namespace Shopware\Psh\ScriptRuntime\Execution;

/**
 * Access template content and write the content to the target destination
 */
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

    /**
     * @param string $source
     * @param string $destination
     */
    public function __construct(string $source, string $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     */
    public function setDestination(string $destination)
    {
        $this->destination = $destination;
    }

    /**
     * @return string
     * @throws TemplateNotValidException
     */
    public function getContent(): string
    {
        if (!file_exists($this->source)) {
            throw new TemplateNotValidException('File source not found in "' . $this->source . '"');
        }

        return file_get_contents($this->source);
    }

    /**
     * @param string $contents
     */
    public function setContents(string $contents)
    {
        file_put_contents($this->destination, $contents);
    }
}
