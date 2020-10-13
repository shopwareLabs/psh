<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use function file_exists;
use function file_get_contents;
use function file_put_contents;

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

    public function __construct(string $source, string $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function setDestination(string $destination)
    {
        $this->destination = $destination;
    }

    /**
     * @throws TemplateNotValidException
     */
    public function getContent(): string
    {
        if (!file_exists($this->source)) {
            throw new TemplateNotValidException('File source not found in "' . $this->source . '"');
        }

        return file_get_contents($this->source);
    }

    public function setContents(string $contents)
    {
        file_put_contents($this->destination, $contents);
    }
}
