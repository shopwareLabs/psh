<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigBuilder;
use Shopware\Psh\Config\ConfigLoader;
use Shopware\Psh\Config\ScriptsPath;
use Shopware\Psh\Config\XmlConfigFileLoader;

class XmlConfigFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    const CONFIG_TEMPLATE = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>

<psh xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:noNamespaceSchemaLocation="../../../resource/config.xsd">
%s
</psh>
EOD;

    const TEMP_FILE = __DIR__ . '/xml_test_file';

    protected function tearDown()
    {
        @unlink(self::TEMP_FILE);
    }

    private function writeTempFile(string $content)
    {
        file_put_contents(self::TEMP_FILE, sprintf(self::CONFIG_TEMPLATE, $content));
    }

    private function createConfigLoader(): XmlConfigFileLoader
    {
        return new XmlConfigFileLoader(new ConfigBuilder(), __DIR__);
    }

    public function test_it_can_be_instantiated()
    {
        $loader = $this->createConfigLoader();
        $this->assertInstanceOf(XmlConfigFileLoader::class, $loader);
        $this->assertInstanceOf(ConfigLoader::class, $loader);
    }

    public function test_it_supports_yaml_files()
    {
        $loader = $this->createConfigLoader();

        $this->assertTrue($loader->isSupported('.psh.xml'));
        $this->assertTrue($loader->isSupported('.psh.xml.dist'));
        $this->assertTrue($loader->isSupported('.psh.xml.override'));

        $this->assertFalse($loader->isSupported('fo.txt'));
        $this->assertFalse($loader->isSupported('.psh.yml'));
        $this->assertFalse($loader->isSupported('fo.yaml.bar'));
    }

    public function test_it_works_if_no_paths_are_present()
    {
        $this->writeTempFile(<<<EOD
<placeholder>
    <dynamic name="filesystem">ls -al</dynamic>
    <const name="FOO">bar</const>
</placeholder>
EOD
);

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);
        $this->assertEquals(['filesystem' => 'ls -al'], $config->getDynamicVariables());
    }

    public function test_it_works_if_no_dynamics_are_present()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<placeholder>
    <const name="FOO">bar</const>
</placeholder>
<path>$dir/_foo</path>
<path>$dir/_bar</path>
EOD
);

        $loader = $this->createConfigLoader();

        $config = $loader->load(self::TEMP_FILE, []);
        $this->assertEquals([ 'FOO' => 'bar'], $config->getConstants());
    }

    public function test_it_works_if_no_consts_are_present()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<placeholder>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
<path>$dir/_foo</path>
<path>$dir/_bar</path>
EOD
);
        $loader = $this->createConfigLoader();

        $config = $loader->load(self::TEMP_FILE, []);

        $scripts = $config->getAllScriptsPaths();
        $this->assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        $this->assertCount(2, $scripts);
        $this->assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        $this->assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
    }

    public function test_it_creates_a_valid_config_file_if_all_required_params_are_present()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
<path>$dir/_foo</path>
<path>$dir/_bar</path>
EOD
);

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);

        $this->assertInstanceOf(Config::class, $config);
    }

    public function test_it_creates_a_valid_config_file_if_all_params_are_present()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<header>foo</header>
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
<path>$dir/_foo</path>
<path>$dir/_bar</path>
EOD
        );

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);

        $this->assertInstanceOf(Config::class, $config);
    }


    public function test_environment_paths_do_not_influence_default_environment()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
<path>$dir/_foo</path>
<environment name="namespace">
    <path>$dir/_bar</path>
</environment>
EOD
);

        $loader =$this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);

        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals([
            'filesystem' => 'ls -al',
        ], $config->getDynamicVariables());

        $this->assertEquals([
            'FOO' => 'bar',
        ], $config->getConstants());

        $scripts = $config->getAllScriptsPaths();
        $this->assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        $this->assertCount(2, $scripts);
        $this->assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        $this->assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
        $this->assertEquals('namespace', $scripts[1]->getNamespace());
    }

    public function test_it_loads_environment_paths()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
<path>$dir/_foo</path>
<environment name="namespace">
    <path>$dir/_bar</path>
</environment>
EOD
);

        $loader =$this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);

        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals([
            'filesystem' => 'ls -al',
        ], $config->getDynamicVariables('namespace'));

        $this->assertEquals([
            'FOO' => 'bar',
        ], $config->getConstants('namespace'));

        $scripts = $config->getAllScriptsPaths();
        $this->assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        $this->assertCount(2, $scripts);
        $this->assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        $this->assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
        $this->assertEquals('namespace', $scripts[1]->getNamespace());
    }

    public function test_it_loads_environments_with_vars()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
<path>$dir/_foo</path>
<environment name="namespace">
    <placeholder>
        <dynamic name="booh">bar</dynamic>    
        <const name="booh">hah</const>    
    </placeholder>
    <path>$dir/_bar</path>
</environment>
EOD
        );

        $loader =$this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);

        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals([
            'filesystem' => 'ls -al',
            'booh' => 'bar'
        ], $config->getDynamicVariables('namespace'));

        $this->assertEquals([
            'FOO' => 'bar',
            'booh' => 'hah'
        ], $config->getConstants('namespace'));

        $scripts = $config->getAllScriptsPaths();
        $this->assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        $this->assertCount(2, $scripts);
        $this->assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        $this->assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
        $this->assertEquals('namespace', $scripts[1]->getNamespace());
    }

    public function test_it_loads_templates()
    {
        $this->writeTempFile(<<<EOD
<template source="_the_template.tpl" destination="the_destination.txt" />
EOD
);

        $loader =$this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);

        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals([
            ['source' => __DIR__ . '/_the_template.tpl', 'destination' => __DIR__ . '/the_destination.txt']
        ], $config->getTemplates());
    }

    public function test_invalid_format()
    {
        $this->writeTempFile(<<<EOD
<not-allowed />
EOD
);

        $loader =$this->createConfigLoader();

        $this->expectException(\InvalidArgumentException::class);
        $loader->load(self::TEMP_FILE, []);
    }

    public function test_multiple_placeholder_elements_are_supported()
    {
        $this->writeTempFile(<<<EOD
<placeholder>
   <const name="FOO">bar</const>
</placeholder>
<placeholder>
    <const name="BAR">foo</const>
</placeholder>
EOD
);

        $loader =$this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);
        $this->assertCount(2, $config->getConstants());
    }

    public function test_multiple_header_work_although_they_overwrite_each_other()
    {
        $this->writeTempFile(<<<EOD
<header>NO</header>
<header>YES</header>
EOD
);

        $loader =$this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);
        $this->assertSame('YES', $config->getHeader());
    }

    public function test_it_loads_dotenv_files()
    {
        $this->writeTempFile(<<<EOD
<placeholder>
    <dotenv>.fiz</dotenv>
    <dotenv>.baz</dotenv>
</placeholder>
EOD
        );

        $loader =$this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);
        $this->assertCount(2, $config->getDotenvPaths(), print_r($config->getDotenvPaths(), true));
        $this->assertEquals(__DIR__ . '/.fiz', $config->getDotenvPaths()['.fiz']->getPath());
        $this->assertEquals(__DIR__ . '/.baz', $config->getDotenvPaths()['.baz']->getPath());
    }

    public function test_it_loads_dotenv_files_from_environments_overwritten()
    {
        $this->writeTempFile(<<<EOD
<placeholder>
    <dotenv>.fiz</dotenv>
    <dotenv>.baz</dotenv>
</placeholder>
<environment name="env">
    <placeholder>
        <dotenv>_foo/.fiz</dotenv>        
        <dotenv>_foo/.buz</dotenv>        
    </placeholder>    
</environment>
EOD
        );

        $loader =$this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, []);

        $this->assertCount(3, $config->getDotenvPaths('env'));
        $this->assertEquals(__DIR__ . '/_foo/.fiz', $config->getDotenvPaths('env')['.fiz']->getPath());
        $this->assertEquals(__DIR__ . '/.baz', $config->getDotenvPaths('env')['.baz']->getPath());
        $this->assertEquals(__DIR__ . '/_foo/.buz', $config->getDotenvPaths('env')['.buz']->getPath());
    }
}
