<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use Shopware\Psh\Config\Template;
use Shopware\Psh\Config\ValueProvider;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;
use function array_merge;

/**
 * Create representation of the current environment variables and constants
 */
class ProcessEnvironment
{
    /**
     * @var ValueProvider[]
     */
    private $constants;

    /**
     * @var ValueProvider[]
     */
    private $variables;

    /**
     * @var Template[]
     */
    private $templates;

    /**
     * @var ValueProvider[]
     */
    private $dotenvVariables;

    /**
     * @param ValueProvider[] $constants
     * @param ValueProvider[] $variables
     * @param Template[] $templates
     * @param ValueProvider[] $dotenvVars
     */
    public function __construct(array $constants, array $variables, array $templates, array $dotenvVars)
    {
        Assert::allIsInstanceOf($constants, ValueProvider::class);
        Assert::allIsInstanceOf($variables, ValueProvider::class);
        Assert::allIsInstanceOf($dotenvVars, ValueProvider::class);
        Assert::allIsInstanceOf($templates, Template::class);

        $this->constants = $constants;
        $this->variables = $variables;
        $this->templates = $templates;
        $this->dotenvVariables = $dotenvVars;
    }

    /**
     * @return ValueProvider[]
     */
    public function getAllValues(): array
    {
        return array_merge(
            $this->constants,
            $this->dotenvVariables,
            $this->variables
        );
    }

    /**
     * @return Template[]
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function createProcess(string $shellCommand): Process
    {
        return new Process($shellCommand);
    }
}
