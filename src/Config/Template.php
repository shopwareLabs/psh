<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Shopware\Psh\ScriptRuntime\Execution\TemplateNotValid;
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

    /**
     * @var string
     */
    private $workingDir;

    public function __construct(string $source, string $destination, string $workingDir)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->workingDir = $workingDir;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }

    public function getWorkingDir(): string
    {
        return $this->workingDir;
    }

    /**
     * @throws TemplateNotValid
     */
    public function getContent(): string
    {
        if (!file_exists($this->source)) {
            throw new TemplateNotValid('File source not found in "' . $this->source . '"');
        }

        return file_get_contents($this->source);
    }

    public function setContents(string $contents): void
    {
        $success = @file_put_contents($this->destination, $contents);

        if ($success === false) {
            throw new TemplateWriteNotSuccessful($this->destination);
        }
    }
}
