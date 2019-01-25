<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use Shopware\Psh\Config\ScriptPath;
use Shopware\Psh\Listing\DescriptionReader;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\ScriptRuntime\Command;
use Shopware\Psh\ScriptRuntime\SynchronusProcessCommand;
use Shopware\Psh\ScriptRuntime\ScriptLoader\BashScriptParser;
use Shopware\Psh\ScriptRuntime\ScriptLoader\CommandBuilder;
use Shopware\Psh\ScriptRuntime\ScriptLoader\PshScriptParser;
use Shopware\Psh\ScriptRuntime\ScriptLoader\ScriptLoader;
use Shopware\Psh\ScriptRuntime\TemplateCommand;

class PshScriptParserTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_loads_all_simple_commands_from_a_script()
    {
        $commands = $this->createCommands(new Script(__DIR__ . '/_scripts', 'simple.sh'));

        $this->assertCount(3, $commands);
        $this->assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        $lastCommand = array_pop($commands);
        $this->assertEquals(5, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertFalse($lastCommand->isIgnoreError());
    }

    public function test_it_concatenates_commands()
    {
        $commands = $this->createCommands(new Script(__DIR__ . '/_scripts', 'concatenate.sh'));

        $this->assertCount(2, $commands);
        $this->assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        $lastCommand = array_pop($commands);
        $this->assertEquals(5, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertFalse($lastCommand->isIgnoreError());
    }

    public function test_it_sets_ignore_error()
    {
        $commands = $this->createCommands(new Script(__DIR__ . '/_scripts', 'ignore_error.sh'));

        $this->assertCount(3, $commands);
        $this->assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        $lastCommand = array_pop($commands);
        $this->assertEquals(5, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertTrue($lastCommand->isIgnoreError());
    }

    public function test_it_sets_tty()
    {
        $commands = $this->createCommands(new Script(__DIR__ . '/_scripts', 'tty.sh'));

        $this->assertCount(1, $commands);
        $this->assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        $lastCommand = array_pop($commands);
        $this->assertEquals(2, $lastCommand->getLineNumber());
        $this->assertEquals('ls -al', $lastCommand->getShellCommand());
        $this->assertTrue($lastCommand->isTty());
    }

    public function test_includes_with_local_commands()
    {
        $commands = $this->createCommands(new Script(__DIR__ . '/_scripts', 'local_include.sh'));

        $this->assertCount(8, $commands);
        $this->assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        $this->assertEquals(2, $commands[0]->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $commands[0]->getShellCommand());
        $this->assertFalse($commands[0]->isIgnoreError());

        $lastCommand = array_pop($commands);
        $this->assertEquals(5, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertFalse($lastCommand->isIgnoreError());
    }

    public function test_include_throws_exception()
    {
        $this->expectException(\RuntimeException::class);
        $this->createCommands(new Script(__DIR__ . '/_scripts', 'exception_include.sh'));
    }

    public function test_action_with_local_commands()
    {
        $loader = new ScriptLoader(new CommandBuilder(), new ScriptFinder([
            new ScriptPath(__DIR__ . '/_scripts/'),
            new ScriptPath(__DIR__ . '/_scripts/', 'env'),
        ], new DescriptionReader()));

        $commands = $loader->loadScript(new Script(__DIR__ . '/_scripts', 'local_action.sh'));

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

    public function test_action_throws_exception()
    {
        $loader = new ScriptLoader(new CommandBuilder(), new ScriptFinder([], new DescriptionReader()));
        $this->expectException(\RuntimeException::class);
        $loader->loadScript(new Script(__DIR__ . '/_scripts', 'exception_action.sh'));
    }

    public function test_renders_templates_on_demand()
    {
        $commands = $this->createCommands(new Script(__DIR__ . '/_scripts', 'template.sh'));

        $this->assertCount(3, $commands);
        $this->assertContainsOnlyInstancesOf(Command::class, $commands);

        $this->assertEquals(2, $commands[0]->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $commands[0]->getShellCommand());
        $this->assertFalse($commands[0]->isIgnoreError());

        $this->assertInstanceOf(TemplateCommand::class, $commands[1]);
        $this->assertEquals(__DIR__ . '/_scripts/complex.sh', $commands[1]->createTemplate()->getDestination());
        $this->assertEquals(file_get_contents(__DIR__ . '/_scripts/simple.sh'), $commands[1]->createTemplate()->getContent());

        $lastCommand = array_pop($commands);
        $this->assertEquals(4, $lastCommand->getLineNumber());
        $this->assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        $this->assertFalse($lastCommand->isIgnoreError());
    }

    /**
     * @param Script $script
     * @return SynchronusProcessCommand[]
     */
    public function createCommands(Script $script): array
    {
        return (new PshScriptParser(new CommandBuilder()))
            ->parseContent(file_get_contents($script->getPath()), $script);
    }
}
