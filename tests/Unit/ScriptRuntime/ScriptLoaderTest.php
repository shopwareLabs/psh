<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\Command;
use Shopware\Psh\ScriptRuntime\CommandBuilder;
use Shopware\Psh\ScriptRuntime\ProcessCommand;
use Shopware\Psh\ScriptRuntime\ScriptLoader;
use Shopware\Psh\ScriptRuntime\TemplateCommand;

class ScriptLoaderTest extends \PHPUnit\Framework\TestCase
{
    public function test_it_loads_all_simple_commands_from_a_script()
    {
        $loader = new ScriptLoader(new CommandBuilder());

        $commands = $loader->loadScript(new Script(__DIR__ . '/_scripts', 'simple.sh'));

        $this->assertCount(3, $commands);
        $this->assertContainsOnlyInstancesOf(ProcessCommand::class, $commands);

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
        $this->assertContainsOnlyInstancesOf(ProcessCommand::class, $commands);

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
        $this->assertContainsOnlyInstancesOf(ProcessCommand::class, $commands);

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
        $this->assertContainsOnlyInstancesOf(ProcessCommand::class, $commands);

        $lastCommand = array_pop($commands);
        $this->assertEquals(2, $lastCommand->getLineNumber());
        $this->assertEquals('ls -al', $lastCommand->getShellCommand());
        $this->assertTrue($lastCommand->isTty());
    }

    public function test_it_includes_local_commands()
    {
        $loader = new ScriptLoader(new CommandBuilder());

        $commands = $loader->loadScript(new Script(__DIR__ . '/_scripts', 'local_include.sh'));

        $this->assertCount(8, $commands);
        $this->assertContainsOnlyInstancesOf(ProcessCommand::class, $commands);

        $this->assertEquals(2, $commands[0]->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $commands[0]->getShellCommand());
        $this->assertFalse($commands[0]->isIgnoreError());

        $lastCommand = array_pop($commands);
        $this->assertEquals(5, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertFalse($lastCommand->isIgnoreError());
    }

    public function test_it_include_throws_exception()
    {
        $loader = new ScriptLoader(new CommandBuilder());
        $this->expectException(\RuntimeException::class);
        $loader->loadScript(new Script(__DIR__ . '/_scripts', 'exception_include.sh'));
    }

    public function test_renders_templates_on_demand()
    {
        $loader = new ScriptLoader(new CommandBuilder());

        $commands = $loader->loadScript(new Script(__DIR__ . '/_scripts', 'template.sh'));

        $this->assertCount(3, $commands);
        $this->assertContainsOnlyInstancesOf(Command::class, $commands);

        $this->assertEquals(2, $commands[0]->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $commands[0]->getShellCommand());
        $this->assertFalse($commands[0]->isIgnoreError());

        $this->assertInstanceOf(TemplateCommand::class, $commands[1]);
        $this->assertEquals(__DIR__ . '/_scripts/complex.sh', $commands[1]->getTemplate()->getDestination());
        $this->assertEquals(file_get_contents(__DIR__ . '/_scripts/simple.sh'), $commands[1]->getTemplate()->getContent());

        $lastCommand = array_pop($commands);
        $this->assertEquals(4, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertFalse($lastCommand->isIgnoreError());
    }
}
