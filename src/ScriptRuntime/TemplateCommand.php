<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

use Shopware\Psh\Config\Template;

class TemplateCommand implements Command
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
     * @var int
     */
    private $lineNumber;

    /**
     * @var string
     */
    private $workingDirectory;

    public function __construct(
        string $source,
        string $destination,
        string $workingDirectory,
        int $lineNumber
    ) {
        $this->source = $source;
        $this->destination = $destination;
        $this->lineNumber = $lineNumber;
        $this->workingDirectory = $workingDirectory;
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function createTemplate(): Template
    {
        return new Template(
            $this->source,
            $this->destination,
            $this->workingDirectory
        );
    }
}
