<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Integration\ScriptRuntime;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\CommandBuilder;
use Shopware\Psh\ScriptRuntime\ProcessEnvironment;
use Shopware\Psh\ScriptRuntime\ProcessExecutor;
use Shopware\Psh\ScriptRuntime\ScriptLoader;
use Shopware\Psh\ScriptRuntime\TemplateEngine;
use Shopware\Psh\Test\BlackholeLogger;

class ProcessExecutorTest extends \PHPUnit_Framework_TestCase
{
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

    /**
     * @before
     * @after
     */
    public function removeState()
    {
        @unlink(__DIR__ . '/_testvalue.tpl');
    }
}
