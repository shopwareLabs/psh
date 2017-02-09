<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Unit\Integration\Listing;

use Shopware\Psh\Listing\Script;
use Shopware\Psh\Listing\ScriptFinder;
use Shopware\Psh\Listing\ScriptPathNotValidException;

class ScriptFinderTest extends \PHPUnit_Framework_TestCase
{
    public function test_script_finder_holds_contract_if_no_paths_present()
    {
        $finder = new ScriptFinder([]);
        $this->assertInstanceOf(ScriptFinder::class, $finder);
        $this->assertInternalType('array', $finder->getAllScripts());
    }

    public function test_script_finder_finds_scripts_if_one_directory_is_passed()
    {
        $finder = new ScriptFinder([__DIR__ . '/_scripts']);
        $this->assertInstanceOf(ScriptFinder::class, $finder);
        $this->assertCount(3, $finder->getAllScripts());
    }

    public function test_script_finder_finds_scripts_if_two_directories_are_passed_and_filters_noise()
    {
        $finder = new ScriptFinder([__DIR__ . '/_scripts', __DIR__ . '/_scripts_with_misc_stuff']);
        $this->assertInstanceOf(ScriptFinder::class, $finder);
        $this->assertCount(5, $finder->getAllScripts());
        $this->assertContainsOnlyInstancesOf(Script::class, $finder->getAllScripts());
    }

    public function test_script_finder_finds_script_by_name_if_two_directories_are_passed_and_filters_noise()
    {
        $finder = new ScriptFinder([__DIR__ . '/_scripts', __DIR__ . '/_scripts_with_misc_stuff']);
        $this->assertInstanceOf(ScriptFinder::class, $finder);

        $script = $finder->findScriptByName('foo');
        $this->assertInstanceOf(Script::class, $script);
        $this->assertEquals('foo', $script->getName());
    }

    public function test_script_finder_prefixes_script_names_with_namespace_if_present()
    {
        $finder = new ScriptFinder([__DIR__ . '/_scripts', 'biz' => __DIR__ . '/_scripts_with_misc_stuff']);
        $this->assertInstanceOf(ScriptFinder::class, $finder);

        $script = $finder->findScriptByName('biz:test');
        $this->assertInstanceOf(Script::class, $script);
        $this->assertEquals('biz:test', $script->getName());
    }

    public function test_script_finder_throws_exception_if_path_is_not_valid()
    {
        $finder = new ScriptFinder([__DIR__ . '/_scripts_not_valid_directory']);
        $this->expectException(ScriptPathNotValidException::class);
        $finder->getAllScripts();
    }
}
