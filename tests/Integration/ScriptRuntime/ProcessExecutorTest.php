<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Integration\ScriptRuntime;

use Shopware\Psh\Listing\DescriptionReader;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\BashCommand;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\ScriptRuntime\DeferredProcessCommand;
use Shopware\Psh\ScriptRuntime\Execution\ExecutionErrorException;
use Shopware\Psh\ScriptRuntime\Execution\ProcessEnvironment;
use Shopware\Psh\ScriptRuntime\Execution\ProcessExecutor;
use Shopware\Psh\ScriptRuntime\Execution\TemplateEngine;
use Shopware\Psh\ScriptRuntime\ScriptLoader\BashScriptParser;
use Shopware\Psh\ScriptRuntime\ScriptLoader\CommandBuilder;
use Shopware\Psh\ScriptRuntime\ScriptLoader\PshScriptParser;
use Shopware\Psh\ScriptRuntime\ScriptLoader\ScriptLoader;
use Shopware\Psh\ScriptRuntime\WaitCommand;
use Shopware\Psh\Test\BlackholeLogger;

class ProcessExecutorTest extends \PHPUnit_Framework_TestCase
{
    const DEFERED_FILES = [
        __DIR__ . '/1.json',
        __DIR__ . '/2.json',
        __DIR__ . '/3.json',
        __DIR__ . '/4.json',
    ];

    protected function tearDown()
    {
        foreach (self::DEFERED_FILES as $file) {
            @unlink($file);
        }
    }

    public function test_environment_and_export_work()
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'environment.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);

        $this->assertEmpty($logger->errors, count($logger->errors) . ' stderr: ' . implode("\n", $logger->errors));
        $this->assertEquals('', trim($logger->output[0]), count($logger->output) . ' stdout: ' . implode("\n", $logger->output));
    }

    public function test_root_dir_is_application_directory()
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'root-dir.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);

        $this->assertEmpty($logger->errors, count($logger->errors) . ' stderr: ' . implode("\n", $logger->errors));
        $this->assertEquals(__DIR__, trim($logger->output[0]), count($logger->output) . ' stdout: ' . implode("\n", $logger->output));
    }

    public function test_template_engine_works_with_template_destinations()
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'root-dir.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            new ProcessEnvironment([
                'VAR' => 'value',
            ], [], [ [
                'source' => __DIR__ . '/_test_read.tpl',
                'destination' => __DIR__ . '/_test__VAR__.tpl'
            ]
            ], []),
            $this->createTemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);

        $this->assertFileExists(__DIR__ . '/_testvalue.tpl');
    }

    public function test_executor_recognises_template_commands()
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'template.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);

        $this->assertFileExists(__DIR__ . '/_testvalue.tpl');
    }

    public function test_non_executable_bash_commands_throw()
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'bash-non-executable.sh');

        $this->expectException(\RuntimeException::class);
        $this->loadCommands($script);
    }

    public function test_non_writable_bash_commands_throw()
    {
        chmod(__DIR__ . '/_non_writable', 0555);
        $script = $this->createScript(__DIR__ . '/_non_writable', 'bash.sh');

        $this->expectException(\RuntimeException::class);
        $this->loadCommands($script);
    }

    public function test_executor_recognises_bash_commands()
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'bash.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(BashCommand::class, $commands[0]);
        $this->assertTrue($commands[0]->hasWarning());

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);
        $this->assertFileNotExists($script->getTmpPath());

        $this->assertStringEndsWith('/psh/tests/Integration/ScriptRuntimeBAR', trim(implode('', $logger->output)));
    }

    public function test_executor_recognises_secure_bash_commands()
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'better_bash.sh');
        $commands = $this->loadCommands($script);
        $logger = new BlackholeLogger();

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(BashCommand::class, $commands[0]);
        $this->assertFalse($commands[0]->hasWarning());

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);
        $this->assertFileNotExists($script->getTmpPath());

        $this->assertCount(1, $logger->output);
        $this->assertStringEndsWith('/psh/tests/Integration/ScriptRuntimeBAR', trim(implode('', $logger->output)));
    }

    public function test_executor_recognises_defered_commands()
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'deferred.sh');
        $commands = $this->loadCommands($script);

        $this->assertCount(5, $commands);
        $this->assertInstanceOf(WaitCommand::class, $commands[2]);
        $this->assertInstanceOf(DeferredProcessCommand::class, $commands[0]);

        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger,
            __DIR__
        );

        $beginExecution = microtime(true);
        $executor->execute($script, $commands);
        $executionTime = microtime(true) - $beginExecution;
        // check a wait occurred
        $totalWait = 0;
        foreach (self::DEFERED_FILES as $file) {
            $this->assertFileExists($file);

            $data = json_decode(file_get_contents($file), true);

            $currentWait = $data['after'] - $data['before'];
            $totalWait += $currentWait;

            $this->assertGreaterThan(0.0001, $currentWait);
        }

        //assert total duration was less then total wait -> Then it just becomes a problem of more processes on travis if necessary
        $this->assertLessThan($executionTime * 0.75, $totalWait);
        $this->assertCount(0, $logger->errors);
        $this->assertEquals(["Done\n", "Done\n", "Done\n", "Done\n"], $logger->output);
    }

    public function test_deferred_commands_get_executed_even_with_error_in_between()
    {
        $script = $this->createScript(__DIR__ . '/_scripts', 'deferred_with_error.sh');
        $commands = $this->loadCommands($script);

        $this->assertCount(4, $commands);
        $this->assertInstanceOf(DeferredProcessCommand::class, $commands[0]);

        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            $this->createProcessEnvironment(),
            $this->createTemplateEngine(),
            $logger,
            __DIR__
        );

        $beginExecution = microtime(true);

        try {
            $executor->execute($script, $commands);
        } catch (ExecutionErrorException $e) {
        }
        $executionTime = microtime(true) - $beginExecution;
        self::assertInstanceOf(ExecutionErrorException::class, $e);

        // check a wait occurred
        $totalWait = 0;
        foreach ([self::DEFERED_FILES[0], self::DEFERED_FILES[1]] as $file) {
            $this->assertFileExists($file);

            $data = json_decode(file_get_contents($file), true);

            $currentWait = $data['after'] - $data['before'];
            $totalWait += $currentWait;

            $this->assertGreaterThan(0.0001, $currentWait);
        }

        //assert total duration was less then total wait -> Then it just becomes a problem of more processes on travis if necessary
        $this->assertLessThan($executionTime, $totalWait);
        $this->assertCount(0, $logger->errors);
        $this->assertEquals(["Done\n", "Done\n"], $logger->output);
    }

    /**
     * @before
     * @after
     */
    public function removeState()
    {
        @unlink(__DIR__ . '/_testvalue.tpl');
    }

    /**
     * @param Script $script
     * @return mixed
     */
    private function loadCommands(Script $script)
    {
        $loader = new ScriptLoader(
            new BashScriptParser(),
            new PshScriptParser(new CommandBuilder(), new ScriptFinder([], new DescriptionReader()))
        );
        return $loader->loadScript($script);
    }

    /**
     * @return ProcessEnvironment
     */
    private function createProcessEnvironment(): ProcessEnvironment
    {
        return new ProcessEnvironment([], [], [], []);
    }

    /**
     * @return TemplateEngine
     */
    private function createTemplateEngine(): TemplateEngine
    {
        return new TemplateEngine();
    }

    private function createScript(string $directory, string $scriptName): Script
    {
        return new Script($directory, $scriptName, false);
    }
}
