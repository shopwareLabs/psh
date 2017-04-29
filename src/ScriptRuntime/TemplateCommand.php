<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime;

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
     * @param string $source
     * @param string $destination
     * @param int $lineNumber
     */
    public function __construct(
        string $source,
        string $destination,
        int $lineNumber
    ) {
        $this->source = $source;
        $this->destination = $destination;
        $this->lineNumber = $lineNumber;
    }

    /**
     * @return int
     */
    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    /**
     * @return Template
     */
    public function getTemplate(): Template
    {
        return new Template(
            $this->source,
            $this->destination
        );
    }
}
