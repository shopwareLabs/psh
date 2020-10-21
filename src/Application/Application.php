<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use InvalidArgumentException;
use Khill\Duration\Duration;
use League\CLImate\CLImate;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\RequiredValue;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\Listing\ScriptNotFoundException;
use Shopware\Psh\ScriptRuntime\Execution\ExecutionErrorException;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function explode;
use function implode;
use function mb_strlen;
use function sprintf;

/**
 * Main application entry point. moves the requested data around and outputs user information.
 */
class Application
{
    const MIN_PADDING_SIZE = 30;

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
     * @return int exit code
     */
    public function run(array $inputArgs): int
    {
        try {
            $config = $this->prepare($inputArgs);

            $scriptFinder = $this->applicationFactory->createScriptFinder($config);

            $this->executeScript($inputArgs, $scriptFinder, $config);

            $this->showListing($scriptFinder->getAllVisibleScripts());

            throw ExitSignal::success();
        } catch (ExitSignal $signal) {
            return $signal->signal();
        }
    }

    /**
     * @param Script[] $scripts
     */
    public function showListing(array $scripts): void
    {
        $this->cliMate->green()->bold('Available commands:')->br();

        if (!count($scripts)) {
            $this->cliMate->yellow()->bold('-> Currently no scripts available');
        }

        $paddingSize = $this->getPaddingSize($scripts);
        $padding = $this->cliMate->padding($paddingSize)->char(' ');

        $scriptEnvironment = false;

        foreach ($scripts as $script) {
            if ($scriptEnvironment !== $script->getEnvironment()) {
                $scriptEnvironment = $script->getEnvironment();
                $this->cliMate->green()->br()->bold(($scriptEnvironment ?? 'default') . ':');
            }

            $padding
                ->label('<bold> - ' . $script->getName() . '</bold>')
                ->result('<dim>' . $script->getDescription() . '</dim>');
        }

        $this->cliMate->green()->bold("\n" . count($scripts) . " script(s) available\n");
    }

    protected function extractScriptNames(array $inputArgs): array
    {
        if (!isset($inputArgs[1])) {
            return [];
        }

        return explode(',', $inputArgs[1]);
    }

    protected function execute(Script $script, Config $config, ScriptFinder $scriptFinder): void
    {
        $commands = $this->applicationFactory
            ->createCommands($script, $scriptFinder);

        $logger = new ClimateLogger($this->cliMate, $this->duration);
        $executor = $this->applicationFactory
            ->createProcessExecutor($script, $config, $logger, $this->rootDirectory);

        try {
            $executor->execute($script, $commands);
        } catch (ExecutionErrorException $e) {
            $this->notifyError("\nExecution aborted, a subcommand failed!\n");

            throw ExitSignal::error();
        }

        $this->notifySuccess("All commands successfully executed!\n");
    }

    /**
     * @param $string
     */
    public function notifySuccess(string $string): void
    {
        $this->cliMate->bold()->green($string);
    }

    /**
     * @param $string
     */
    public function notifyError(string $string): void
    {
        $this->cliMate->bold()->red($string);
    }

    /**
     * @param $config
     */
    protected function printHeader(Config $config): void
    {
        $this->cliMate->green()->bold()->out("\n###################");

        if ($config->getHeader()) {
            $this->cliMate->out("\n" . $config->getHeader());
        }
    }

    private function getPaddingSize(array $scripts): int
    {
        $maxScriptNameLength = 0;
        foreach ($scripts as $script) {
            if (mb_strlen($script->getName()) > $maxScriptNameLength) {
                $maxScriptNameLength = mb_strlen($script->getName());
            }
        }

        return $maxScriptNameLength + self::MIN_PADDING_SIZE;
    }

    private function showAutocompleteListing(Config $config): void
    {
        $scriptFinder = $this->applicationFactory
            ->createScriptFinder($config);

        $scripts = $scriptFinder->getAllVisibleScripts();

        $commands = array_map(function (Script $script) {
            return $script->getName();
        }, $scripts);

        $this->cliMate->out(implode(' ', $commands));
    }

    private function showScriptNotFoundListing(ScriptNotFoundException $ex, array $scriptNames, ScriptFinder $scriptFinder): void
    {
        $this->notifyError("Script with name {$ex->getScriptName()} not found\n");

        $scripts = [];
        foreach ($scriptNames as $scriptName) {
            $newScripts = $scriptFinder->findScriptsByPartialName($scriptName);
            $scripts = array_merge($scripts, $newScripts);
        }

        if (count($scripts) > 0) {
            $this->cliMate->yellow()->bold('Have you been looking for this?');
            $this->showListing($scripts);
        }
    }

    private function printHead(Config $config, ApplicationConfigLogger $logger): void
    {
        $this->printHeader($config);
        $logger->printOut($this->cliMate);
    }

    private function validateConfig(Config $config, string $environment = null): void
    {
        $allPlaceholders = $config->getAllPlaceholders($environment);

        $missing = [];
        foreach ($config->getRequiredVariables($environment) as $requiredVariable) {
            if (!array_key_exists($requiredVariable->getName(), $allPlaceholders)) {
                $missing[] = $requiredVariable;
                $this->printMissingRequiredVariable($requiredVariable);
            }
        }

        if (count($missing)) {
            $this->cliMate->error("\n<bold>Please define the missing value(s) first</bold>\n");
            throw ExitSignal::error();
        }
    }

    private function printMissingRequiredVariable(RequiredValue $requiredVariable): void
    {
        if ($requiredVariable->hasDescription()) {
            $this->cliMate->error(sprintf(
                "\t - <bold>Missing required const or var named <underline>%s</underline></bold> <dim>(%s)</dim>",
                $requiredVariable->getName(),
                $requiredVariable->getDescription()
            ));
        } else {
            $this->cliMate->error(sprintf(
                "\t - <bold>Missing required const or var named <underline>%s</underline></bold>",
                $requiredVariable->getName()
            ));
        }
    }

    private function prepare(array $inputArgs): Config
    {
        $configLogger = new ApplicationConfigLogger($this->rootDirectory, $this->cliMate);

        try {
            $config = $this->applicationFactory
                ->createConfig($configLogger, $this->rootDirectory, $inputArgs);
        } catch (InvalidParameterException $e) {
            $this->notifyError($e->getMessage() . "\n");

            throw ExitSignal::error();
        } catch (InvalidArgumentException $e) {
            $this->notifyError("\n" . $e->getMessage() . "\n");

            throw ExitSignal::error();
        }

        if (count($inputArgs) > 1 && $inputArgs[1] === 'bash_autocompletion_dump') {
            $this->showAutocompleteListing($config);

            throw ExitSignal::success();
        }

        $this->printHead($config, $configLogger);
        $this->validateConfig($config);

        return $config;
    }

    private function executeScript(array $inputArgs, ScriptFinder $scriptFinder, Config $config): void
    {
        $scriptNames = $this->extractScriptNames($inputArgs);

        if (!count($scriptNames)) {
            return;
        }

        try {
            foreach ($scriptNames as $scriptName) {
                $this->execute($scriptFinder->findScriptByName($scriptName), $config, $scriptFinder);
            }
        } catch (ScriptNotFoundException $e) {
            $this->showScriptNotFoundListing($e, $scriptNames, $scriptFinder);

            throw ExitSignal::error();
        }

        throw ExitSignal::success();
    }
}
