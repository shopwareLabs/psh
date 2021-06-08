<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use Khill\Duration\Duration;
use League\CLImate\CLImate;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\RequiredValue;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\Listing\ScriptNotFound;
use Shopware\Psh\PshErrorMessage;
use Shopware\Psh\ScriptRuntime\Execution\ExecutionError;
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

            $this->executeScript($scriptFinder, $config);

            $this->showListing($scriptFinder->getAllVisibleScripts());

            throw ExitSignal::success();
        } catch (PshErrorMessage $error) {
            $this->notifyError("\n" . $error->getMessage() . "\n");

            return ExitSignal::error()->signal();
        } catch (ExitSignal $signal) {
            return $signal->signal();
        }
    }

    /**
     * @param Script[] $scripts
     */
    private function showListing(array $scripts): void
    {
        $this->cliMate->green()->bold('Available commands:')->br();

        if (!count($scripts)) {
            $this->cliMate->yellow()->bold("-> Currently no scripts available\n");
            return;
        }

        $paddingSize = $this->getPaddingSize($scripts);
        $padding = $this->cliMate->padding($paddingSize)->char(' ');

        $scriptEnvironment = 'default';
        foreach ($scripts as $script) {
            if ($script->getEnvironment() !== $scriptEnvironment) {
                $scriptEnvironment = $script->getEnvironment();
                $this->cliMate->green()->br()->bold(($scriptEnvironment ?? 'default') . ':');
            }

            $padding
                ->label(sprintf('<bold> - %s</bold>', $script->getName()))
                ->result(sprintf('<dim>%s</dim>', $script->getDescription()));
        }

        $this->cliMate->green()->bold(sprintf("\n %s script(s) available\n", count($scripts)));
    }

    private function execute(Script $script, Config $config, ScriptFinder $scriptFinder): void
    {
        $commands = $this->applicationFactory
            ->createCommands($script, $scriptFinder);

        $logger = new ClimateLogger($this->cliMate, $this->duration);
        $executor = $this->applicationFactory
            ->createProcessExecutor($script, $config, $logger);

        try {
            $executor->execute($script, $commands);
        } catch (ExecutionError $e) {
            $this->notifyError("\nExecution aborted, a subcommand failed!\n");

            throw ExitSignal::error();
        }

        $this->notifySuccess("All commands successfully executed!\n");
    }

    /**
     * @param $string
     */
    private function notifySuccess(string $string): void
    {
        $this->cliMate->bold()->green($string);
    }

    /**
     * @param $string
     */
    private function notifyError(string $string): void
    {
        $this->cliMate->bold()->red($string);
    }

    private function getPaddingSize(array $scripts): int
    {
        return self::MIN_PADDING_SIZE + max(array_map(static function (Script $script) {
            return mb_strlen($script->getName());
        }, $scripts));
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

    private function showScriptNotFoundListing(string $scriptName, ScriptFinder $scriptFinder): void
    {
        $this->notifyError(sprintf("Script with name %s not found\n", $scriptName));

        $scripts = $scriptFinder->findScriptsByPartialName($scriptName);

        if (count($scripts) > 0) {
            $this->cliMate->yellow()->bold('Have you been looking for this?');
        }

        $this->showListing($scripts);
    }

    private function printHead(Config $config, ApplicationConfigLogger $logger): void
    {
        if($config->hasOption(ApplicationOptions::FLAG_NO_HEADER)) {
            return;
        }

        $this->cliMate->green()->bold()->out("\n###################");

        if ($config->getHeader()) {
            $this->cliMate->out("\n" . $config->getHeader());
        }

        $logger->printOut($this->cliMate);
    }

    private function validateConfig(Config $config, ?string $environment = null): void
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

        $config = $this->applicationFactory
            ->createConfig($configLogger, $this->rootDirectory, $inputArgs);

        if (count($inputArgs) > 1 && $inputArgs[1] === 'bash_autocompletion_dump') {
            $this->showAutocompleteListing($config);

            throw ExitSignal::success();
        }

        $this->printHead($config, $configLogger);
        $this->validateConfig($config);

        return $config;
    }

    private function executeScript(ScriptFinder $scriptFinder, Config $config): void
    {
        $scriptNames = $config->getScriptNames();

        if (!count($scriptNames)) {
            return;
        }

        try {
            $scripts = $scriptFinder->findScriptsInOrder($scriptNames);
        } catch (ScriptNotFound $e) {
            $this->showScriptNotFoundListing($e->getScriptName(), $scriptFinder);
            throw ExitSignal::error();
        }

        foreach ($scripts as $script) {
            $this->execute($script, $config, $scriptFinder);
        }

        throw ExitSignal::success();
    }
}
