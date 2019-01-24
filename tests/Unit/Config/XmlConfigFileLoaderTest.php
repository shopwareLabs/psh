<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigBuilder;
use Shopware\Psh\Config\ConfigLoader;
use Shopware\Psh\Config\ScriptPath;
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
<path>$dir/_foo</path>
<path>$dir/_bar</path>
<placeholder>
    <const name="FOO">bar</const>
</placeholder>
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
<path>$dir/_foo</path>
<path>$dir/_bar</path>
<placeholder>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
EOD
);
        $loader = $this->createConfigLoader();

        $config = $loader->load(self::TEMP_FILE, []);

        $scripts = $config->getAllScriptPaths();
        $this->assertContainsOnlyInstancesOf(ScriptPath::class, $scripts);
        $this->assertCount(2, $scripts);
        $this->assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        $this->assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
    }

    public function test_it_creates_a_valid_config_file_if_all_required_params_are_present()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<path>$dir/_foo</path>
<path>$dir/_bar</path>
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
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
<path>$dir/_foo</path>
<path>$dir/_bar</path>
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
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
<path>$dir/_foo</path>
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
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

        $scripts = $config->getAllScriptPaths();
        $this->assertContainsOnlyInstancesOf(ScriptPath::class, $scripts);
        $this->assertCount(2, $scripts);
        $this->assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        $this->assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
        $this->assertEquals('namespace', $scripts[1]->getNamespace());
    }

    public function test_it_loads_environment_paths()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<path>$dir/_foo</path>
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
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

        $scripts = $config->getAllScriptPaths();
        $this->assertContainsOnlyInstancesOf(ScriptPath::class, $scripts);
        $this->assertCount(2, $scripts);
        $this->assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        $this->assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
        $this->assertEquals('namespace', $scripts[1]->getNamespace());
    }

    public function test_it_loads_environments_with_vars()
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<path>$dir/_foo</path>
<placeholder>
    <const name="FOO">bar</const>
    <dynamic name="filesystem">ls -al</dynamic>
</placeholder>
<environment name="namespace">
    <path>$dir/_bar</path>
    <placeholder>
        <dynamic name="booh">bar</dynamic>    
        <const name="booh">hah</const>    
    </placeholder>
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

        $scripts = $config->getAllScriptPaths();
        $this->assertContainsOnlyInstancesOf(ScriptPath::class, $scripts);
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
}
