<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shopware\Psh\Application\RuntimeParameters;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigBuilder;
use Shopware\Psh\Config\ConfigFileLoader;
use Shopware\Psh\Config\ScriptsPath;
use Shopware\Psh\Config\Template;
use Shopware\Psh\Config\XmlConfigFileLoader;
use function count;
use function file_put_contents;
use function print_r;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

class XmlConfigFileLoaderTest extends TestCase
{
    private const CONFIG_TEMPLATE = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>

<psh xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:noNamespaceSchemaLocation="../../../resource/config.xsd">
%s
</psh>
EOD;

    public const TEMP_FILE = __DIR__ . '/xml_test_file';

    protected function tearDown(): void
    {
        @unlink(self::TEMP_FILE);
    }

    private function writeTempFile(string $content): void
    {
        file_put_contents(self::TEMP_FILE, sprintf(self::CONFIG_TEMPLATE, $content));
    }

    private function createConfigLoader(): XmlConfigFileLoader
    {
        return new XmlConfigFileLoader(new ConfigBuilder(), __DIR__);
    }

    public function test_it_can_be_instantiated(): void
    {
        $loader = $this->createConfigLoader();
        self::assertInstanceOf(XmlConfigFileLoader::class, $loader);
        self::assertInstanceOf(ConfigFileLoader::class, $loader);
    }

    public function test_it_supports_yaml_files(): void
    {
        $loader = $this->createConfigLoader();

        self::assertTrue($loader->isSupported('.psh.xml'));
        self::assertTrue($loader->isSupported('.psh.xml.dist'));
        self::assertTrue($loader->isSupported('.psh.xml.override'));

        self::assertFalse($loader->isSupported('fo.txt'));
        self::assertFalse($loader->isSupported('.psh.yml'));
        self::assertFalse($loader->isSupported('fo.yaml.bar'));
    }

    public function test_it_works_if_no_paths_are_present(): void
    {
        $this->writeTempFile(<<<EOD
<placeholder>
    <dynamic name="filesystem">ls -al</dynamic>
    <const name="FOO">bar</const>
</placeholder>
EOD
);

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());
        $this->assertVariables($config, ['FILESYSTEM' => 'ls -al']);
    }

    public function test_it_works_if_no_dynamics_are_present(): void
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

        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());
        $this->assertConstants($config, ['FOO' => 'bar']);
    }

    public function test_it_works_if_no_consts_are_present(): void
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

        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        $scripts = $config->getAllScriptsPaths();
        self::assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        self::assertCount(2, $scripts);
        self::assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        self::assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
    }

    public function test_it_creates_a_valid_config_file_if_all_required_params_are_present(): void
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
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        self::assertInstanceOf(Config::class, $config);
    }

    public function test_it_creates_a_valid_config_file_if_all_params_are_present(): void
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
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        self::assertInstanceOf(Config::class, $config);
    }

    public function test_environment_paths_do_not_influence_default_environment(): void
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

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        self::assertInstanceOf(Config::class, $config);

        $this->assertVariables($config, [
            'FILESYSTEM' => 'ls -al',
        ]);

        $this->assertConstants($config, [
            'FOO' => 'bar',
        ]);

        $scripts = $config->getAllScriptsPaths();
        self::assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        self::assertCount(2, $scripts);
        self::assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        self::assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
        self::assertEquals('namespace', $scripts[1]->getNamespace());
    }

    public function test_environment_hidden_get_loaded(): void
    {
        $dir = __DIR__;

        $this->writeTempFile(<<<EOD
<path>$dir/_foo</path>
<environment name="namespace" hidden="true">
    <path>$dir/_bar</path>
</environment>
EOD
);

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        self::assertTrue($config->getEnvironments()['namespace']->isHidden());

        self::assertFalse($config->getAllScriptsPaths()[0]->isHidden());
        self::assertTrue($config->getAllScriptsPaths()[1]->isHidden());
    }

    public function test_it_loads_environment_paths(): void
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

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        self::assertInstanceOf(Config::class, $config);

        $this->assertVariables($config, [
            'FILESYSTEM' => 'ls -al',
        ]);

        $this->assertConstants($config, [
            'FOO' => 'bar',
        ]);

        $scripts = $config->getAllScriptsPaths();
        self::assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        self::assertCount(2, $scripts);
        self::assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        self::assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
        self::assertEquals('namespace', $scripts[1]->getNamespace());
        self::assertFalse($config->getEnvironments()['namespace']->isHidden());
    }

    public function test_it_loads_environments_with_vars(): void
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

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        self::assertInstanceOf(Config::class, $config);

        $this->assertVariables($config, [
            'FILESYSTEM' => 'ls -al',
            'BOOH' => 'bar',
        ], 'namespace');

        $this->assertConstants($config, [
            'FOO' => 'bar',
            'BOOH' => 'hah',
        ], 'namespace');

        $scripts = $config->getAllScriptsPaths();
        self::assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        self::assertCount(2, $scripts);
        self::assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        self::assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
        self::assertEquals('namespace', $scripts[1]->getNamespace());
    }

    public function test_it_loads_templates(): void
    {
        $this->writeTempFile(<<<EOD
<template source="_the_template.tpl" destination="the_destination.txt" />
EOD
);

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        self::assertInstanceOf(Config::class, $config);

        self::assertEquals([
            new Template(__DIR__ . '/_the_template.tpl', 'the_destination.txt', __DIR__),
        ], $config->getTemplates());
    }

    public function test_it_loads_templates_with_absolute_destination_untouched(): void
    {
        $tempDir = sys_get_temp_dir();

        $this->writeTempFile(<<<EOD
<template source="_the_template.tpl" destination="$tempDir/the_destination.txt" />
EOD
);

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        self::assertEquals([
            new Template(__DIR__ . '/_the_template.tpl', $tempDir . '/the_destination.txt', __DIR__),
        ], $config->getTemplates());
    }

    public function test_invalid_format(): void
    {
        $this->writeTempFile(<<<EOD
<not-allowed />
EOD
);

        $loader = $this->createConfigLoader();

        $this->expectException(InvalidArgumentException::class);
        $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());
    }

    public function test_multiple_placeholder_elements_are_supported(): void
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

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());
        self::assertCount(2, $config->getConstants());
    }

    public function test_multiple_header_work_although_they_overwrite_each_other(): void
    {
        $this->writeTempFile(<<<EOD
<header>NO</header>
<header>YES</header>
EOD
);

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());
        self::assertSame('YES', $config->getHeader());
    }

    public function test_it_loads_dotenv_files(): void
    {
        $this->writeTempFile(<<<EOD
<placeholder>
    <dotenv>.fiz</dotenv>
    <dotenv>.baz</dotenv>
</placeholder>
EOD
        );

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());
        self::assertCount(2, $config->getDotenvPaths(), print_r($config->getDotenvPaths(), true));
        self::assertEquals(__DIR__ . '/.fiz', $config->getDotenvPaths()['.fiz']->getPath());
        self::assertEquals(__DIR__ . '/.baz', $config->getDotenvPaths()['.baz']->getPath());
    }

    public function test_it_loads_dotenv_files_from_environments_overwritten(): void
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

        $loader = $this->createConfigLoader();
        $config = $loader->load(self::TEMP_FILE, $this->createRuntimeParameters());

        self::assertCount(3, $config->getDotenvPaths('env'));
        self::assertEquals(__DIR__ . '/_foo/.fiz', $config->getDotenvPaths('env')['.fiz']->getPath());
        self::assertEquals(__DIR__ . '/.baz', $config->getDotenvPaths('env')['.baz']->getPath());
        self::assertEquals(__DIR__ . '/_foo/.buz', $config->getDotenvPaths('env')['.buz']->getPath());
    }

    private function assertConstants(Config $config, array $keyValues, ?string $environment = null): void
    {
        foreach ($keyValues as $key => $value) {
            self::assertArrayHasKey($key, $config->getConstants($environment));
            self::assertSame($value, $config->getConstants($environment)[$key]->getValue());
        }

        self::assertCount(count($keyValues), $config->getConstants($environment));
    }

    private function assertVariables(Config $config, array $keyValues, ?string $environment = null): void
    {
        foreach ($keyValues as $key => $value) {
            self::assertArrayHasKey($key, $config->getDynamicVariables($environment));
            self::assertSame($value, $config->getDynamicVariables($environment)[$key]->getCommand());
        }

        self::assertCount(count($keyValues), $config->getDynamicVariables($environment));
    }

    protected function createRuntimeParameters(array $overrideParams = []): RuntimeParameters
    {
        return new RuntimeParameters([], [], $overrideParams);
    }
}
