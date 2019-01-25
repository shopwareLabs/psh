<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Integration\ScriptRuntime;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\CommandBuilder;
use Shopware\Psh\ScriptRuntime\ProcessEnvironment;
use Shopware\Psh\ScriptRuntime\ProcessExecutor;
use Shopware\Psh\ScriptRuntime\ScriptLoader;
use Shopware\Psh\ScriptRuntime\TemplateEngine;
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
        $script = new Script(__DIR__ . '/_scripts', 'environment.sh');
        $loader = new ScriptLoader(new CommandBuilder());
        $commands = $loader->loadScript($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            new ProcessEnvironment([], [], []),
            new TemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);

        $this->assertEmpty($logger->errors, count($logger->errors) . ' stderr: ' . implode("\n", $logger->errors));
        $this->assertEquals('', trim($logger->output[0]), count($logger->output) . ' stdout: ' . implode("\n", $logger->output));
    }

    public function test_root_dir_is_application_directory()
    {
        $script = new Script(__DIR__ . '/_scripts', 'root-dir.sh');
        $loader = new ScriptLoader(new CommandBuilder());
        $commands = $loader->loadScript($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            new ProcessEnvironment([], [], []),
            new TemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);

        $this->assertEmpty($logger->errors, count($logger->errors) . ' stderr: ' . implode("\n", $logger->errors));
        $this->assertEquals(__DIR__, trim($logger->output[0]), count($logger->output) . ' stdout: ' . implode("\n", $logger->output));
    }

    public function test_template_engine_works_with_template_destinations()
    {
        $script = new Script(__DIR__ . '/_scripts', 'root-dir.sh');
        $loader = new ScriptLoader(new CommandBuilder());
        $commands = $loader->loadScript($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            new ProcessEnvironment([
                'VAR' => 'value',
            ], [], [ [
                'source' => __DIR__ . '/_test_read.tpl',
                'destination' => __DIR__ . '/_test__VAR__.tpl'
            ]
            ]),
            new TemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);

        $this->assertFileExists(__DIR__ . '/_testvalue.tpl');
    }

    public function test_executor_recognises_template_commands()
    {
        $script = new Script(__DIR__ . '/_scripts', 'template.sh');
        $loader = new ScriptLoader(new CommandBuilder());
        $commands = $loader->loadScript($script);
        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            new ProcessEnvironment([], [], []),
            new TemplateEngine(),
            $logger,
            __DIR__
        );

        $executor->execute($script, $commands);

        $this->assertFileExists(__DIR__ . '/_testvalue.tpl');
    }

    public function test_executor_recognises_defered_commands()
    {
        $script = new Script(__DIR__ . '/_scripts', 'deferred.sh');
        $loader = new ScriptLoader(new CommandBuilder());

        $commands = $loader->loadScript($script);

        $this->assertCount(5, $commands);
        $this->assertInstanceOf(WaitCommand::class, $commands[2]);
        $this->assertTrue($commands[0]->isDeferred());

        $logger = new BlackholeLogger();

        $executor = new ProcessExecutor(
            new ProcessEnvironment([], [], []),
            new TemplateEngine(),
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

    /**
     * @before
     * @after
     */
    public function removeState()
    {
        @unlink(__DIR__ . '/_testvalue.tpl');
    }
}
