<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigBuilder;
use Shopware\Psh\Config\ConfigLoader;
use Shopware\Psh\Config\YamlConfigFileLoader;
use Symfony\Component\Yaml\Parser;

class YamlConfigFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    private function createConfigLoader(Parser $parser = null)
    {
        if (!$parser) {
            $parser = new Parser();
        }

        return new YamlConfigFileLoader($parser, new ConfigBuilder());
    }

    public function test_it_can_be_instantiated()
    {
        $loader = $this->createConfigLoader();
        $this->assertInstanceOf(YamlConfigFileLoader::class, $loader);
        $this->assertInstanceOf(ConfigLoader::class, $loader);
    }

    public function test_it_supports_yaml_files()
    {
        $loader = $this->createConfigLoader();

        $this->assertTrue($loader->isSupported('fo.yaml'));
        $this->assertTrue($loader->isSupported('fo.yml'));

        $this->assertFalse($loader->isSupported('fo.txt'));
        $this->assertFalse($loader->isSupported('fo.yaml.dist'));
    }

    public function test_it_works_if_no_paths_are_present()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);

        $loader = $this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt');
        $this->assertEquals(['filesystem' => 'ls -al'], $config->getDynamicVariables());
    }

    public function test_it_works_if_no_dynamics_are_present()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/_foo',
                __DIR__ . '/_bar',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);

        $loader = $this->createConfigLoader($yamlMock->reveal());

        $config = $loader->load(__DIR__ . '/_test.txt');
        $this->assertEquals([ 'FOO' => 'bar'], $config->getConstants());
    }

    public function test_it_works_if_no_consts_are_present()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/_foo',
                __DIR__ . '/_bar',
            ],
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
        ]);

        $loader = $this->createConfigLoader($yamlMock->reveal());

        $config = $loader->load(__DIR__ . '/_test.txt');
        $this->assertEquals([__DIR__ . '/_foo', __DIR__ . '/_bar'], $config->getAllScriptPaths());
    }

    public function test_it_creates_a_valid_config_file_if_all_required_params_are_present()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/_foo',
                __DIR__ . '/_bar',
            ],
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);


        $loader = $this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt');

        $this->assertInstanceOf(Config::class, $config);
    }

    public function test_it_creates_a_valid_config_file_if_all_params_are_present()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'header' => 'foo',
            'paths' => [
                __DIR__ . '/_foo',
                __DIR__ . '/_bar',
            ],
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);

        $loader = $this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt');

        $this->assertInstanceOf(Config::class, $config);
    }


    public function test_environment_paths_do_not_influence_default_environment()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/_foo'
            ],
            'environments' => [
                'namespace' => [
                    'paths' => [
                        __DIR__ . '/_bar',
                    ]
                ],
            ],
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);

        $loader =$this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt');

        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals([
            'filesystem' => 'ls -al',
        ], $config->getDynamicVariables());

        $this->assertEquals([
            'FOO' => 'bar',
        ], $config->getConstants());

        $this->assertEquals([
            __DIR__ . '/_foo',
            'namespace' => __DIR__ . '/_bar',
        ], $config->getAllScriptPaths());
    }

    public function test_it_loads_environment_paths()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/_foo'
            ],
            'environments' => [
                'namespace' => [
                    'paths' => [
                        __DIR__ . '/_bar',
                    ]
                ],
            ],
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);

        $loader =$this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt');

        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals([
            'filesystem' => 'ls -al',
        ], $config->getDynamicVariables('namespace'));

        $this->assertEquals([
            'FOO' => 'bar',
        ], $config->getConstants('namespace'));

        $this->assertEquals([
            __DIR__ . '/_foo',
            'namespace' => __DIR__ . '/_bar',
        ], $config->getAllScriptPaths());
    }

    public function test_it_loads_environments_with_vars()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/_foo'
            ],
            'environments' => [
                'namespace' => [
                    'paths' => [
                        __DIR__ . '/_bar',
                    ],
                    'dynamic' => [
                        'booh' => 'bar'
                    ],
                    'const' => [
                        'booh' => 'hah',
                    ]
                ],
            ],
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);

        $loader =$this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt');

        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals([
            'filesystem' => 'ls -al',
            'booh' => 'bar'
        ], $config->getDynamicVariables('namespace'));

        $this->assertEquals([
            'FOO' => 'bar',
            'booh' => 'hah'
        ], $config->getConstants('namespace'));

        $this->assertEquals([
            __DIR__ . '/_foo',
            'namespace' => __DIR__ . '/_bar',
        ], $config->getAllScriptPaths());
    }

    public function test_it_loads_templates()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
            ],
            'templates' => [
                ['source' => '_the_template.tpl', 'destination' => 'the_destination.txt']
            ]
        ]);

        $loader =$this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt');

        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals([
            ['source' => __DIR__ . '/_the_template.tpl', 'destination' => __DIR__ . '/the_destination.txt']
        ], $config->getTemplates());
    }
}
