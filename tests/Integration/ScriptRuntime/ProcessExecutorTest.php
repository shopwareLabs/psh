<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Integration\ScriptRuntime;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Psh\Config\EnvironmentResolver;
use Shopware\Psh\Config\Template;
use Shopware\Psh\Listing\DescriptionReader;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\ScriptRuntime\BashCommand;
use Shopware\Psh\ScriptRuntime\Command;
use Shopware\Psh\ScriptRuntime\DeferredProcessCommand;
use Shopware\Psh\ScriptRuntime\Execution\ExecutionError;
use Shopware\Psh\ScriptRuntime\Execution\ProcessEnvironment;
use Shopware\Psh\ScriptRuntime\Execution\ProcessExecutor;
use Shopware\Psh\ScriptRuntime\Execution\TemplateEngine;
use Shopware\Psh\ScriptRuntime\ScriptLoader\BashScriptParser;
use Shopware\Psh\ScriptRuntime\ScriptLoader\CommandBuilder;
use Shopware\Psh\ScriptRuntime\ScriptLoader\PshScriptParser;
use Shopware\Psh\ScriptRuntime\ScriptLoader\ScriptLoader;
use Shopware\Psh\ScriptRuntime\WaitCommand;
use Shopware\Psh\Test\BlackholeLogger;
use function chmod;
use function count;
use function file_get_contents;
use function implode;
use function json_decode;
use function microtime;
use function trim;
use function unlink;

class ProcessExecutorTest extends TestCase
{
    private const DEFERED_FILES = [
        __DIR__ . '/1.json',
        __DIR__ . '/2.json',
        __DIR__ . '/3.json',
        __DIR__ . '/4.json',
    ];

    protected function tearDown(): void
    {
        foreach (self::DEFERED_FILES as $file) {
            @unlink($file);
        }
    }

    public function test_environment_and_export_work(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'environment.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger
        );

        $executor->execute($script, $commands);

        self::assertEmpty($logger->errors, count($logger->errors) . ' stderr: ' . implode("\n", $logger->errors));
        self::assertEquals('', trim($logger->output[0]), count($logger->output) . ' stdout: ' . implode("\n", $logger->output));
    }

    public function test_root_dir_is_application_directory(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'root-dir.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger
        );

        $executor->execute($script, $commands);

        self::assertEmpty($logger->errors, count($logger->errors) . ' stderr: ' . implode("\n", $logger->errors));
        self::assertEquals(__DIR__, trim($logger->output[0]), count($logger->output) . ' stdout: ' . implode("\n", $logger->output));
    }

    public function test_template_engine_works_with_template_destinations(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'root-dir.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            new ProcessEnvironment((new EnvironmentResolver())->resolveConstants([
                'VAR' => 'value',
            ]), [], $this->resolveTemplates([[
                'source' => __DIR__ . '/_test_read.tpl',
                'destination' => '_test__VAR__.tpl',
            ]], __DIR__), []),
            $this->createTemplateEngine(),
            $logger
        );

        $executor->execute($script, $commands);

        self::assertFileExists(__DIR__ . '/_testvalue.tpl');
    }

    public function test_executor_recognises_template_commands(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'template.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger
        );

        $executor->execute($script, $commands);

        self::assertFileExists(__DIR__ . '/_testvalue.tpl');
    }

    public function test_non_executable_bash_commands_throw(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'bash-non-executable.sh');

        $this->expectException(RuntimeException::class);
        $this->loadCommands($script);
    }

    public function test_non_writable_bash_commands_throw(): void
    {
        chmod(__DIR__ . '/_non_writable', 0555);
        $script = $this->createScript(__DIR__ . '/_non_writable', 'bash.sh');

        $this->expectException(RuntimeException::class);
        $this->loadCommands($script);
    }

    public function test_executor_recognises_bash_commands(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'bash.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        self::assertCount(1, $commands);
        self::assertInstanceOf(BashCommand::class, $commands[0]);
        self::assertTrue($commands[0]->hasWarning());

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger
        );

        $executor->execute($script, $commands);
        self::assertFileNotExists($script->getTmpPath());

        self::assertStringEndsWith('/psh/tests/Integration/ScriptRuntimeBAR', trim(implode('', $logger->output)));
    }

    public function test_executor_executes_two_bash_commands_subsequently(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'bash_include.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        self::assertCount(6, $commands);
        self::assertInstanceOf(BashCommand::class, $commands[1]);
        self::assertTrue($commands[1]->hasWarning());
        self::assertInstanceOf(BashCommand::class, $commands[2]);
        self::assertTrue($commands[2]->hasWarning());
        self::assertInstanceOf(BashCommand::class, $commands[4]);
        self::assertTrue($commands[4]->hasWarning());
        self::assertSame(1, $commands[4]->getLineNumber());
        self::assertFalse($commands[4]->isIgnoreError());

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger
        );

        $executor->execute($script, $commands);
        self::assertFileNotExists($script->getTmpPath());

        self::assertSame([
            __DIR__  . PHP_EOL,
            __DIR__ . 'BAR' . PHP_EOL,
            __DIR__ . 'BAR' . PHP_EOL,
            __DIR__  . PHP_EOL,
            __DIR__ . 'BAR' . PHP_EOL,
            __DIR__  . PHP_EOL,
        ], $logger->output);
    }

    public function test_executor_recognises_secure_bash_commands(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'better_bash.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        self::assertCount(1, $commands);
        self::assertInstanceOf(BashCommand::class, $commands[0]);
        self::assertFalse($commands[0]->hasWarning());

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger
        );

        $executor->execute($script, $commands);
        self::assertFileNotExists($script->getTmpPath());

        self::assertCount(1, $logger->output);
        self::assertStringEndsWith('/psh/tests/Integration/ScriptRuntimeBAR', trim(implode('', $logger->output)));
    }

    public function test_executor_recognises_defered_commands(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'deferred.sh');
        $commands = $this->loadCommands($script);

        self::assertCount(5, $commands);
        self::assertInstanceOf(WaitCommand::class, $commands[2]);
        self::assertInstanceOf(DeferredProcessCommand::class, $commands[0]);

        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger
        );

        $beginExecution = microtime(true);
        $executor->execute($script, $commands);
        $executionTime = microtime(true) - $beginExecution;
        // check a wait occurred
        $totalWait = 0;
        foreach (self::DEFERED_FILES as $file) {
            $totalWait = $this->assertDeferredFile($file, $totalWait);
        }

        //assert total duration was less then total wait -> Then it just becomes a problem of more processes on travis if necessary
        self::assertLessThan($executionTime * 0.75, $totalWait);
        self::assertCount(0, $logger->errors);
        self::assertEquals(["Done\n", "Done\n", "Done\n", "Done\n"], $logger->output);
    }

    public function test_deferred_commands_get_executed_even_with_sync_error_in_between(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'deferred_with_sync_error.sh');
        $commands = $this->loadCommands($script);

        self::assertCount(4, $commands);
        self::assertInstanceOf(DeferredProcessCommand::class, $commands[0]);

        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger
        );

        $beginExecution = microtime(true);

        try {
            $executor->execute($script, $commands);
        } catch (ExecutionError $e) {
        }
        $executionTime = microtime(true) - $beginExecution;
        self::assertInstanceOf(ExecutionError::class, $e);

        // check a wait occurred
        $totalWait = 0;
        foreach ([self::DEFERED_FILES[0], self::DEFERED_FILES[1]] as $file) {
            $totalWait = $this->assertDeferredFile($file, $totalWait);
        }

        //assert total duration was less then total wait -> Then it just becomes a problem of more processes on travis if necessary
        self::assertLessThan($executionTime, $totalWait);
        self::assertCount(0, $logger->errors);
        self::assertEquals(["Done\n", "Done\n"], $logger->output);
    }

    public function test_deferred_commands_get_executed_even_with_deferred_error_in_between(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'deferred_with_deferred_error.sh');
        $commands = $this->loadCommands($script);

        self::assertCount(4, $commands);
        self::assertInstanceOf(DeferredProcessCommand::class, $commands[0]);

        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger
        );

        $beginExecution = microtime(true);

        try {
            $executor->execute($script, $commands);
        } catch (ExecutionError $e) {
        }
        $executionTime = microtime(true) - $beginExecution;
        self::assertInstanceOf(ExecutionError::class, $e);

        // check a wait occurred
        $totalWait = 0;
        $totalWait = $this->assertDeferredFile(self::DEFERED_FILES[0], $totalWait);
        self::assertFileNotExists(self::DEFERED_FILES[1]);
        self::assertFileNotExists(self::DEFERED_FILES[2]);

        //assert total duration was less then total wait -> Then it just becomes a problem of more processes on travis if necessary
        self::assertLessThan($executionTime, $totalWait);
        self::assertCount(0, $logger->errors);
        self::assertSame(2, $logger->failures);
        self::assertSame(2, $logger->successes);
        self::assertEquals(["Done\n", "Done\n"], $logger->output);
    }

    public function test_unkbownh_command_throws(): void
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'deferred_with_deferred_error.sh');
        $commands = [new class() implements Command {
            public function getLineNumber(): int
            {
                return PHP_INT_MAX;
            }
        }];

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            new BlackholeLogger()
        );

        $this->expectException(InvalidArgumentException::class);
        $executor->execute($script, $commands);
    }

    /**
     * @before
     * @after
     */
    public function removeState(): void
    {
        @unlink(__DIR__ . '/_testvalue.tpl');
    }

    private function loadCommands(Script $script): array
    {
        $loader = new ScriptLoader(
            new BashScriptParser(),
            new PshScriptParser(new CommandBuilder(), new ScriptFinder([], new DescriptionReader()))
        );

        return $loader->loadScript($script);
    }

    private function createProcessEnvironment(): ProcessEnvironment
    {
        return new ProcessEnvironment([], [], [], []);
    }

    private function createTemplateEngine(): TemplateEngine
    {
        return new TemplateEngine();
    }

    private function createScript(string $directory, string $scriptName): Script
    {
        return new Script($directory, $scriptName, false, __DIR__);
    }

    private function assertDeferredFile(string $file, float $totalWait): float
    {
        self::assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);

        $currentWait = $data['after'] - $data['before'];
        $totalWait += $currentWait;

        self::assertGreaterThan(0.0001, $currentWait);

        return $totalWait;
    }

    private function resolveTemplates(array $templates, string $workingDirectory): array
    {
        $resolvedVariables = [];
        foreach ($templates as $template) {
            $resolvedVariables[] = new Template($template['source'], $template['destination'], $workingDirectory);
        }

        return $resolvedVariables;
    }
}
