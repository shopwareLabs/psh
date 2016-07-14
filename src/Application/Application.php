<?php declare(strict_types = 1);


namespace Shopware\Psh\Application;


use League\CLImate\CLImate;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigFileFinder;
use Shopware\Psh\Config\YamlConfigFileLoader;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\ScriptRuntime\CommandBuilder;
use Shopware\Psh\ScriptRuntime\Environment;
use Shopware\Psh\ScriptRuntime\ExecutionErrorException;
use Shopware\Psh\ScriptRuntime\ProcessExecutor;
use Shopware\Psh\ScriptRuntime\ScriptLoader;
use Shopware\Psh\ScriptRuntime\TemplateEngine;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Parser;

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

    public function __construct(string $rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
        $this->cliMate = new CLImate();
    }

    private function setUpConfig(): Config
    {
        $configFinder = new ConfigFileFinder();
        $configFile = $configFinder->discoverFile($this->rootDirectory);

        $configLoader = new YamlConfigFileLoader(new Parser());

        if(!$configLoader->isSupported($configFile)) {
            throw new \RuntimeException('Unable to reaf configuration from "' . $configFile . '"');
        }

        return $configLoader->load($configFile, $this->rootDirectory);
    }

    private function setUpScripts(Config $config): array
    {
        $listing = new ScriptFinder($config->getScriptPaths());
        return $listing->getAllScripts();
    }

    private function findScripts(Config $config, string $scriptName): Script
    {
        $listing = new ScriptFinder($config->getScriptPaths());
        return $listing->findScriptByName($scriptName);
    }

    public function run(array $inputArgs)
    {
        $config = $this->setUpConfig();
        $allScripts = $this->setUpScripts($config);

        $this->printHeader($config);

        if(count($inputArgs) > 1) {
            $this->execute($this->findScripts($config, $inputArgs[1]), $config);
            return;
        }

        $this->showListing($allScripts);
    }

    public function showListing(array $scripts)
    {
        $this->cliMate->green()->bold("Available commands\n");

        if(!count($scripts)) {
            $this->cliMate->yellow()->bold("-> Currently no scripts available");
        }

        foreach($scripts as $script) {
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
        $scriptLoader = new ScriptLoader(new CommandBuilder());

        $logger = new ClimateLogger($this->cliMate);
        $commands = $scriptLoader->loadScript($script);
        $executor = new ProcessExecutor(
            new Environment($config->getConstants(), $config->getDynamicVariables()),
            new TemplateEngine(),
            $logger,
            $this->rootDirectory
        );

        try {
            $executor->execute($script, $commands);
        } catch (ExecutionErrorException $e) {
            $this->exitWithError("\nExecution aborted, a subcommand failed!\n");
            return;
        }

        $this->exitWithSuccess("\nAll commands sucessfull executed!\n");
    }

    public function exitWithSuccess($string) {
        $this->cliMate->bold()->green($string);
    }

    public function exitWithError($string) {
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