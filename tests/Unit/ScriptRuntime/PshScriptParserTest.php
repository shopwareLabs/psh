<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Psh\Config\ScriptsPath;
use Shopware\Psh\Listing\DescriptionReader;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\ScriptRuntime\Command;
use Shopware\Psh\ScriptRuntime\ScriptLoader\CommandBuilder;
use Shopware\Psh\ScriptRuntime\ScriptLoader\PshScriptParser;
use Shopware\Psh\ScriptRuntime\ScriptLoader\ScriptLoader;
use Shopware\Psh\ScriptRuntime\SynchronusProcessCommand;
use Shopware\Psh\ScriptRuntime\TemplateCommand;
use function array_pop;
use function file_get_contents;

class PshScriptParserTest extends TestCase
{
    public function test_it_loads_all_simple_commands_from_a_script(): void
    {
        $commands = $this->createCommands($this->createScript(__DIR__ . '/_scripts', 'simple.sh'));

        self::assertCount(3, $commands);
        self::assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        $lastCommand = array_pop($commands);
        self::assertEquals(5, $lastCommand->getLineNumber());
        self::assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        self::assertFalse($lastCommand->isIgnoreError());
    }

    public function test_it_concatenates_commands(): void
    {
        $commands = $this->createCommands($this->createScript(__DIR__ . '/_scripts', 'concatenate.sh'));

        self::assertCount(2, $commands);
        self::assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        $lastCommand = array_pop($commands);
        self::assertEquals(5, $lastCommand->getLineNumber());
        self::assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        self::assertFalse($lastCommand->isIgnoreError());
    }

    public function test_it_sets_ignore_error(): void
    {
        $commands = $this->createCommands($this->createScript(__DIR__ . '/_scripts', 'ignore_error.sh'));

        self::assertCount(3, $commands);
        self::assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        $lastCommand = array_pop($commands);
        self::assertEquals(5, $lastCommand->getLineNumber());
        self::assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        self::assertTrue($lastCommand->isIgnoreError());
    }

    public function test_it_sets_tty(): void
    {
        $commands = $this->createCommands($this->createScript(__DIR__ . '/_scripts', 'tty.sh'));

        self::assertCount(1, $commands);
        self::assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        $lastCommand = array_pop($commands);
        self::assertEquals(2, $lastCommand->getLineNumber());
        self::assertEquals('ls -al', $lastCommand->getShellCommand());
        self::assertTrue($lastCommand->isTty());
    }

    public function test_includes_with_local_commands(): void
    {
        $commands = $this->createCommands($this->createScript(__DIR__ . '/_scripts', 'local_include.sh'));

        self::assertCount(8, $commands);
        self::assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        self::assertEquals(2, $commands[0]->getLineNumber());
        self::assertEquals('bin/phpunit --debug --verbose', $commands[0]->getShellCommand());
        self::assertFalse($commands[0]->isIgnoreError());

        $lastCommand = array_pop($commands);
        self::assertEquals(5, $lastCommand->getLineNumber());
        self::assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        self::assertFalse($lastCommand->isIgnoreError());
    }

    public function test_include_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->createCommands($this->createScript(__DIR__ . '/_scripts', 'exception_include.sh'));
    }

    public function test_action_with_local_commands(): void
    {
        $commands = $this->createCommands($this->createScript(__DIR__ . '/_scripts', 'local_action.sh'), [
            new ScriptsPath(__DIR__ . '/_scripts/', __DIR__, false),
            new ScriptsPath(__DIR__ . '/_scripts/', __DIR__, false, 'env'),
        ]);

        self::assertCount(8, $commands);
        self::assertContainsOnlyInstancesOf(SynchronusProcessCommand::class, $commands);

        self::assertEquals(2, $commands[0]->getLineNumber());
        self::assertEquals('bin/phpunit --debug --verbose', $commands[0]->getShellCommand());
        self::assertFalse($commands[0]->isIgnoreError());

        $lastCommand = array_pop($commands);
        self::assertEquals(5, $lastCommand->getLineNumber());
        self::assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        self::assertFalse($lastCommand->isIgnoreError());
    }

    public function test_action_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to find script named "action_not_exists"');
        $this->createCommands($this->createScript(__DIR__ . '/_scripts', 'exception_action.sh'));
    }

    public function test_renders_templates_on_demand(): void
    {
        $commands = $this->createCommands($this->createScript(__DIR__ . '/_scripts', 'template.sh'));

        self::assertCount(3, $commands);
        self::assertContainsOnlyInstancesOf(Command::class, $commands);

        self::assertEquals(2, $commands[0]->getLineNumber());
        self::assertEquals('bin/phpunit --debug --verbose', $commands[0]->getShellCommand());
        self::assertFalse($commands[0]->isIgnoreError());

        self::assertInstanceOf(TemplateCommand::class, $commands[1]);
        self::assertEquals('complex.sh', $commands[1]->createTemplate()->getDestination());
        self::assertEquals(file_get_contents(__DIR__ . '/_scripts/simple.sh'), $commands[1]->createTemplate()->getContent());

        $lastCommand = array_pop($commands);
        self::assertEquals(4, $lastCommand->getLineNumber());
        self::assertEquals('bin/phpunit --debug --verbose', $lastCommand->getShellCommand());
        self::assertFalse($lastCommand->isIgnoreError());
    }

    /**
     * @return SynchronusProcessCommand[]
     */
    public function createCommands(Script $script, array $availableSubScripts = []): array
    {
        $scriptLoader = new ScriptLoader(
            new PshScriptParser(new CommandBuilder(), new ScriptFinder($availableSubScripts, new DescriptionReader()))
        );

        return $scriptLoader->loadScript($script);
    }

    private function createScript(string $directory, string $scriptName): Script
    {
        return new Script($directory, $scriptName, false, __DIR__);
    }
}
