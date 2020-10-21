<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Integration\Listing;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Config\ScriptsPath;
use Shopware\Psh\Listing\DescriptionReader;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\Listing\ScriptPathNotValidException;

class ScriptFinderTest extends TestCase
{
    public function test_script_finder_holds_contract_if_no_paths_present(): void
    {
        $finder = new ScriptFinder([], new DescriptionReader());
        self::assertInstanceOf(ScriptFinder::class, $finder);
        self::assertIsArray($finder->getAllScripts());
    }

    public function test_script_finder_finds_scripts_if_one_directory_is_passed(): void
    {
        $finder = new ScriptFinder(
            [$this->createScriptsPath(__DIR__ . '/_scripts')],
            new DescriptionReader()
        );

        self::assertInstanceOf(ScriptFinder::class, $finder);
        self::assertCount(4, $finder->getAllScripts());
    }

    public function test_script_finder_finds_scripts_if_two_directories_are_passed_and_filters_noise(): void
    {
        $finder = new ScriptFinder(
            [$this->createScriptsPath(__DIR__ . '/_scripts'), $this->createScriptsPath(__DIR__ . '/_scripts_with_misc_stuff')],
            new DescriptionReader()
        );

        $scripts = $finder->getAllScripts();
        self::assertInstanceOf(ScriptFinder::class, $finder);
        self::assertCount(6, $scripts);
        self::assertContainsOnlyInstancesOf(Script::class, $finder->getAllScripts());

        self::assertFalse($scripts['test']->isHidden());
        self::assertTrue($scripts['.hidden']->isHidden());
    }

    public function test_script_finder_finds_script_by_name_if_two_directories_are_passed_and_filters_noise(): void
    {
        $finder = new ScriptFinder(
            [$this->createScriptsPath(__DIR__ . '/_scripts'), $this->createScriptsPath(__DIR__ . '/_scripts_with_misc_stuff')],
            new DescriptionReader()
        );
        self::assertInstanceOf(ScriptFinder::class, $finder);

        $script = $finder->findScriptByName('foo');
        self::assertInstanceOf(Script::class, $script);
        self::assertEquals('foo', $script->getName());
    }

    public function test_script_finder_prefixes_script_names_with_namespace_if_present(): void
    {
        $finder = new ScriptFinder(
            [$this->createScriptsPath(__DIR__ . '/_scripts'), $this->createScriptsPath(__DIR__ . '/_scripts_with_misc_stuff', 'biz')],
            new DescriptionReader()
        );
        self::assertInstanceOf(ScriptFinder::class, $finder);

        $script = $finder->findScriptByName('biz:test');
        self::assertInstanceOf(Script::class, $script);
        self::assertEquals('biz:test', $script->getName());
    }

    public function test_script_finder_throws_exception_if_path_is_not_valid(): void
    {
        $finder = new ScriptFinder(
            [$this->createScriptsPath(__DIR__ . '/_scripts_not_valid_directory')],
            new DescriptionReader()
        );

        $this->expectException(ScriptPathNotValidException::class);
        $finder->getAllScripts();
    }

    public function test_script_finder_adds_description_to_script(): void
    {
        $finder = new ScriptFinder(
            [$this->createScriptsPath(__DIR__ . '/_scripts')],
            new DescriptionReader()
        );

        $script = $finder->findScriptByName('description');
        self::assertSame('My description', $script->getDescription());
    }

    public function test_script_finder_finds_partial_name(): void
    {
        $finder = new ScriptFinder(
            [$this->createScriptsPath(__DIR__ . '/_scripts'), $this->createScriptsPath(__DIR__ . '/_scripts_with_misc_stuff')],
            new DescriptionReader()
        );

        self::assertInstanceOf(ScriptFinder::class, $finder);
        self::assertCount(2, $finder->findScriptsByPartialName('test'));
        self::assertContainsOnlyInstancesOf(Script::class, $finder->getAllScripts());
    }

    private function createScriptsPath(string $path, string $namespace = null): ScriptsPath
    {
        return new ScriptsPath($path, false, $namespace);
    }
}
