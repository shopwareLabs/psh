<?php declare (strict_types = 1);


namespace Shopware\Psh\Application;

use League\CLImate\CLImate;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\ExecutionErrorException;

class Application
{
    /**
     * @var CLImate
     */
    public $cliMate;

    /**
     * @var string
     */
    private $rootDirectory;

    /**
     * @var ApplicationFactory
     */
    private $applicationFactory;

    public function __construct(string $rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
        $this->applicationFactory = new ApplicationFactory();
        $this->cliMate = new CLImate();
    }

    public function run(array $inputArgs)
    {
        $config = $this->applicationFactory
            ->createConfig($this->rootDirectory);

        $scriptFinder = $this->applicationFactory
            ->createScriptFinder($config);

        $this->printHeader($config);

        if (count($inputArgs) > 1) {
            $this->execute($scriptFinder->findScriptByName($inputArgs[1]), $config);
            return;
        }

        $this->showListing($scriptFinder->getAllScripts());
    }

    public function showListing(array $scripts)
    {
        $this->cliMate->green()->bold("Available commands\n");

        if (!count($scripts)) {
            $this->cliMate->yellow()->bold("-> Currently no scripts available");
        }

        foreach ($scripts as $script) {
            $this->cliMate->tab()->out("- " . $script->getName());
        }

        $this->cliMate->green()->bold("\n" . count($scripts) . " script(s) available\n");
    }

    /**
     * @param Script $script
     * @param Config $config
     */
    protected function execute(Script $script, Config $config)
    {
        $commands = $this->applicationFactory
            ->createCommands($script);

        $logger = new ClimateLogger($this->cliMate);
        $executor = $this->applicationFactory
            ->createProcessExecutor($script, $config, $logger, $this->rootDirectory);

        try {
            $executor->execute($script, $commands);
        } catch (ExecutionErrorException $e) {
            $this->exitWithError("\nExecution aborted, a subcommand failed!\n");
            return;
        }

        $this->exitWithSuccess("\nAll commands successfully executed!\n");
    }

    public function exitWithSuccess($string)
    {
        $this->cliMate->bold()->green($string);
    }

    public function exitWithError($string)
    {
        $this->cliMate->bold()->red($string);
    }

    /**
     * @param $config
     */
    protected function printHeader($config)
    {
        $this->cliMate->green()->bold()->out("\n###################");
        if ($config->getHeader()) {
            $this->cliMate->out("\n" . $config->getHeader());
        }
    }
}
