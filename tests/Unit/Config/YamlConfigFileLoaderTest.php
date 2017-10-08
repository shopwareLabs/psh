<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigBuilder;
use Shopware\Psh\Config\ConfigLoader;
use Shopware\Psh\Config\LocalConfigPlaceholder;
use Shopware\Psh\Config\ScriptPath;
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
        $this->assertTrue($loader->isSupported('fo.yml.dist'));
        $this->assertTrue($loader->isSupported('fo.yaml.dist'));
        $this->assertTrue($loader->isSupported('fo.yml.override'));
        $this->assertTrue($loader->isSupported('fo.yaml.override'));

        $this->assertFalse($loader->isSupported('fo.txt'));
        $this->assertFalse($loader->isSupported('fo.yaml.bar'));
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
        $config = $loader->load(__DIR__ . '/_test.txt', []);
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

        $config = $loader->load(__DIR__ . '/_test.txt', []);
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

        $config = $loader->load(__DIR__ . '/_test.txt', []);

        $scripts = $config->getAllScriptPaths();
        $this->assertContainsOnlyInstancesOf(ScriptPath::class, $scripts);
        $this->assertCount(2, $scripts);
        $this->assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        $this->assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
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
        $config = $loader->load(__DIR__ . '/_test.txt', []);

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
        $config = $loader->load(__DIR__ . '/_test.txt', []);

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
        $config = $loader->load(__DIR__ . '/_test.txt', []);

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
        $config = $loader->load(__DIR__ . '/_test.txt', []);

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
        $config = $loader->load(__DIR__ . '/_test.txt', []);

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
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
            ],
            'templates' => [
                ['source' => '_the_template.tpl', 'destination' => 'the_destination.txt']
            ]
        ]);

        $loader =$this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt', []);

        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals([
            ['source' => __DIR__ . '/_the_template.tpl', 'destination' => __DIR__ . '/the_destination.txt']
        ], $config->getTemplates());
    }

    public function test_it_loads_a_by_description_minimal_local_placeholder()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
            ],
            'local' => [
                'foo' => [
                    'description' =>  'DESCRIPTION',
                ]
            ]
        ]);

        $loader =$this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt', []);

        $this->assertInstanceOf(Config::class, $config);

        $this->assertCount(1, $config->getLocals());
        $this->assertContainsOnlyInstancesOf(LocalConfigPlaceholder::class, $config->getLocals());
        $this->assertEquals('foo', $config->getLocals()[0]->getName());
        $this->assertEquals('DESCRIPTION', $config->getLocals()[0]->getDescription());
        $this->assertEquals('', $config->getLocals()[0]->getDefault());
        $this->assertTrue($config->getLocals()[0]->isStorable());
    }

    public function test_it_loads_a_by_default_minimal_local_placeholder()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
            ],
            'local' => [
                'foo' => [
                      'default' =>  'DEFAULT',
                ]
            ]
        ]);

        $loader =$this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt', []);

        $this->assertInstanceOf(Config::class, $config);

        $this->assertCount(1, $config->getLocals());
        $this->assertContainsOnlyInstancesOf(LocalConfigPlaceholder::class, $config->getLocals());


        $this->assertCount(1, $config->getLocals());
        $this->assertContainsOnlyInstancesOf(LocalConfigPlaceholder::class, $config->getLocals());
        $this->assertEquals('foo', $config->getLocals()[0]->getName());
        $this->assertEquals('', $config->getLocals()[0]->getDescription());
        $this->assertEquals('DEFAULT', $config->getLocals()[0]->getDefault());
        $this->assertTrue($config->getLocals()[0]->isStorable());
    }

    public function test_it_throws_on_invalid_local_placeholder()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
            ],
            'local' => [
                'foo' => [
                ]
            ]
        ]);

        $loader =$this->createConfigLoader($yamlMock->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $loader->load(__DIR__ . '/_test.txt', []);
    }


    public function test_it_can_say_if_a_local_placeholder_is_resolved()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
            ],
            'const' => [
                'bAr' => 'bar-value',
            ],
            'dynamic' => [
                'bAz' => 'bAz-value',
            ],
            'local' => [
                'foo' => [
                    'default' =>  'DEFAULT',
                ],
                'bar' => [
                    'default' =>  'DEFAULT',
                ],
                'bAZ' => [
                    'default' => 'DEFAULT',
                ]
            ]
        ]);

        $loader =$this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt', []);

        $this->assertInstanceOf(Config::class, $config);

        $this->assertCount(1, $config->getUnresolvedLocalPlaceholders());
    }
}
