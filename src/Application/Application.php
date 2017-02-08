<?php declare(strict_types=1);


namespace Shopware\Psh\Application;

use Khill\Duration\Duration;
use League\CLImate\CLImate;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptNotFoundException;
use Shopware\Psh\Listing\ScriptPathNotValidException;
use Shopware\Psh\ScriptRuntime\ExecutionErrorException;
use Shopware\Psh\ScriptRuntime\TemplateNotValidException;

/**
 * Main application entry point. moves the requested data around and outputs user information.
 */
class Application
{
    const RESULT_SUCCESS = 0;

    const RESULT_ERROR = 1;

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

    /**
     * @var Duration
     */
    private $duration;

    /**
     * @param string $rootDirectory
     */
    public function __construct(string $rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
        $this->applicationFactory = new ApplicationFactory();
        $this->cliMate = new CLImate();
        $this->duration = new Duration();
    }

    /**
     * Main entry point to execute the application.
     *
     * @param array $inputArgs
     * @return int exit code
     */
    public function run(array $inputArgs): int
    {
        $config = $this->applicationFactory
            ->createConfig($this->rootDirectory);

        $scriptFinder = $this->applicationFactory
            ->createScriptFinder($config);

        $this->printHeader($config);
        $scriptNames = $this->extractScriptNames($inputArgs);

        try {
            foreach ($scriptNames as $scriptName) {
                $executionExitCode = $this->execute($scriptFinder->findScriptByName($scriptName), $config);

                if ($executionExitCode !== self::RESULT_SUCCESS) {
                    return $executionExitCode;
                }
            }

            if (isset($executionExitCode)) {
                return $executionExitCode;
            }
        } catch (ScriptNotFoundException $e) {
            $this->notifyError("Script with name {$inputArgs[1]} not found\n");
            return self::RESULT_ERROR;
        }

        try {
            $this->showListing($scriptFinder->getAllScripts());
        } catch (ScriptPathNotValidException $e) {
            $this->notifyError($e->getMessage() . "\n");
            return self::RESULT_ERROR;
        }

        return self::RESULT_SUCCESS;
    }

    /**
     * @param array $scripts
     */
    public function showListing(array $scripts)
    {
        $this->cliMate->green()->bold("Available commands:\n");

        if (!count($scripts)) {
            $this->cliMate->yellow()->bold('-> Currently no scripts available');
        }

        foreach ($scripts as $script) {
            $this->cliMate->tab()->out('- ' . $script->getName());
        }

        $this->cliMate->green()->bold("\n" . count($scripts) . " script(s) available\n");
    }

    /**
     * @param array $inputArgs
     * @return array
     */
    protected function extractScriptNames(array $inputArgs): array
    {
        if (!isset($inputArgs[1])) {
            return [];
        }

        return explode(',', $inputArgs[1]);
    }

    /**
     * @param Script $script
     * @param Config $config
     * @return int
     */
    protected function execute(Script $script, Config $config): int
    {
        $commands = $this->applicationFactory
            ->createCommands($script);

        $logger = new ClimateLogger($this->cliMate, $this->duration);
        $executor = $this->applicationFactory
            ->createProcessExecutor($script, $config, $logger, $this->rootDirectory);

        try {
            $executor->execute($script, $commands);
        } catch (ExecutionErrorException $e) {
            $this->notifyError("\nExecution aborted, a subcommand failed!\n");
            return self::RESULT_ERROR;
        } catch (TemplateNotValidException $e) {
            $this->notifyError("\n" . $e->getMessage() . "\n");
            return self::RESULT_ERROR;
        }

        $this->notifySuccess("All commands successfully executed!\n");

        return self::RESULT_SUCCESS;
    }

    /**
     * @param $string
     */
    public function notifySuccess($string)
    {
        $this->cliMate->bold()->green($string);
    }

    /**
     * @param $string
     */
    public function notifyError($string)
    {
        $this->cliMate->bold()->red($string);
    }

    /**
     * @param $config
     */
    protected function printHeader(Config $config)
    {
        $this->cliMate->green()->bold()->out("\n###################");

        if ($config->getHeader()) {
            $this->cliMate->out("\n" . $config->getHeader());
        }
    }
}
