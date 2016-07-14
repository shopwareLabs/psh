<?php declare(strict_types = 1);

namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\CommandBuilder;
use Shopware\Psh\ScriptRuntime\ScriptLoader;

class ScriptLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_loads_all_simple_commands_from_a_script()
    {
        $loader = new ScriptLoader(new CommandBuilder());

        $commands = $loader->loadScript(new Script(__DIR__ . '/_scripts', 'simple.sh'));

        $this->assertCount(3, $commands);

        $lastCommand = array_pop($commands);
        $this->assertEquals(5, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertFalse($lastCommand->isIgnoreError());
    }

    public function test_it_concatenates_commands()
    {
        $loader = new ScriptLoader(new CommandBuilder());

        $commands = $loader->loadScript(new Script(__DIR__ . '/_scripts', 'concatenate.sh'));

        $this->assertCount(2, $commands);

        $lastCommand = array_pop($commands);
        $this->assertEquals(5, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertFalse($lastCommand->isIgnoreError());
    }

    public function test_it_sets_ignore_error()
    {
        $loader = new ScriptLoader(new CommandBuilder());

        $commands = $loader->loadScript(new Script(__DIR__ . '/_scripts', 'ignore_error.sh'));

        $this->assertCount(3, $commands);

        $lastCommand = array_pop($commands);
        $this->assertEquals(5, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertTrue($lastCommand->isIgnoreError());
    }

    public function test_it_sets_tty()
    {
        $loader = new ScriptLoader(new CommandBuilder());

        $commands = $loader->loadScript(new Script(__DIR__ . '/_scripts', 'tty.sh'));

        $this->assertCount(1, $commands);

        $lastCommand = array_pop($commands);
        $this->assertEquals(2, $lastCommand->getLineNumber());
        $this->assertEquals('ls -al', $lastCommand->getShellCommand());
        $this->assertTrue($lastCommand->isTty());
    }

    public function test_it_includes_local_commands()
    {
        $loader = new ScriptLoader(new CommandBuilder());

        $commands = $loader->loadScript(new Script(__DIR__ . '/_scripts', 'local_include.sh'));

        $this->assertCount(5, $commands);

        $this->assertEquals(2, $commands[0]->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $commands[0]->getShellCommand());
        $this->assertFalse($commands[0]->isIgnoreError());

        $lastCommand = array_pop($commands);
        $this->assertEquals(4, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertFalse($lastCommand->isIgnoreError());
    }
}