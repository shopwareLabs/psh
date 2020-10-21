<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigBuilder;
use Shopware\Psh\Config\ConfigLoader;
use Shopware\Psh\Config\ScriptsPath;
use Shopware\Psh\Config\Template;
use Shopware\Psh\Config\YamlConfigFileLoader;
use Symfony\Component\Yaml\Parser;
use function count;

class YamlConfigFileLoaderTest extends TestCase
{
    private function createConfigLoader(?Parser $parser = null)
    {
        if (!$parser) {
            $parser = new Parser();
        }

        return new YamlConfigFileLoader($parser, new ConfigBuilder(), __DIR__);
    }

    public function test_it_can_be_instantiated(): void
    {
        $loader = $this->createConfigLoader();
        self::assertInstanceOf(YamlConfigFileLoader::class, $loader);
        self::assertInstanceOf(ConfigLoader::class, $loader);
    }

    public function test_it_supports_yaml_files(): void
    {
        $loader = $this->createConfigLoader();

        self::assertTrue($loader->isSupported('.psh.yaml'));
        self::assertTrue($loader->isSupported('.psh.yml'));
        self::assertTrue($loader->isSupported('.psh.yml.dist'));
        self::assertTrue($loader->isSupported('.psh.yaml.dist'));
        self::assertTrue($loader->isSupported('.psh.yml.override'));
        self::assertTrue($loader->isSupported('.psh.yaml.override'));

        self::assertFalse($loader->isSupported('fo.txt'));
        self::assertFalse($loader->isSupported('fo.yml'));
        self::assertFalse($loader->isSupported('fo.yaml.bar'));
    }

    public function test_it_works_if_no_paths_are_present(): void
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
        $this->assertVariables($config, ['filesystem' => 'ls -al']);
    }

    public function test_it_works_if_no_dynamics_are_present(): void
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
        $this->assertConstants($config, ['FOO' => 'bar']);
    }

    public function test_it_works_if_no_consts_are_present(): void
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

        $scripts = $config->getAllScriptsPaths();
        self::assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        self::assertCount(2, $scripts);
        self::assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        self::assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
    }

    public function test_it_creates_a_valid_config_file_if_all_required_params_are_present(): void
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

        self::assertInstanceOf(Config::class, $config);
    }

    public function test_it_creates_a_valid_config_file_if_all_params_are_present(): void
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

        self::assertInstanceOf(Config::class, $config);
    }

    public function test_environment_paths_do_not_influence_default_environment(): void
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/_foo',
            ],
            'environments' => [
                'namespace' => [
                    'paths' => [
                        __DIR__ . '/_bar',
                    ],
                ],
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

        self::assertInstanceOf(Config::class, $config);

        $this->assertVariables($config, [
            'filesystem' => 'ls -al',
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

    public function test_it_loads_environment_paths(): void
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/_foo',
            ],
            'environments' => [
                'namespace' => [
                    'paths' => [
                        __DIR__ . '/_bar',
                    ],
                ],
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

        self::assertInstanceOf(Config::class, $config);

        $this->assertVariables($config, [
            'filesystem' => 'ls -al',
        ], 'namespace');

        $this->assertConstants($config, [
            'FOO' => 'bar',
        ], 'namespace');

        $scripts = $config->getAllScriptsPaths();
        self::assertContainsOnlyInstancesOf(ScriptsPath::class, $scripts);
        self::assertCount(2, $scripts);
        self::assertEquals(__DIR__ . '/_foo', $scripts[0]->getPath());
        self::assertEquals(__DIR__ . '/_bar', $scripts[1]->getPath());
        self::assertEquals('namespace', $scripts[1]->getNamespace());
    }

    public function test_it_loads_environments_with_vars(): void
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/_foo',
            ],
            'environments' => [
                'namespace' => [
                    'paths' => [
                        __DIR__ . '/_bar',
                    ],
                    'dynamic' => [
                        'booh' => 'bar',
                    ],
                    'const' => [
                        'booh' => 'hah',
                    ],
                ],
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

        self::assertInstanceOf(Config::class, $config);

        $this->assertVariables($config, [
            'filesystem' => 'ls -al',
            'booh' => 'bar',
        ], 'namespace');

        $this->assertConstants($config, [
            'FOO' => 'bar',
            'booh' => 'hah',
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
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
            ],
            'templates' => [
                ['source' => '_the_template.tpl', 'destination' => 'the_destination.txt'],
            ],
        ]);

        $loader = $this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt', []);

        self::assertInstanceOf(Config::class, $config);

        self::assertEquals([
            new Template(__DIR__ . '/_the_template.tpl', __DIR__ . '/the_destination.txt'),
        ], $config->getTemplates());
    }

    public function test_it_loads_dotenv_files(): void
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [],
            'dotenv' => [
                '.fiz',
                '.baz',
            ],
        ]);

        $loader = $this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt', []);

        self::assertCount(2, $config->getDotenvPaths());
        self::assertEquals(__DIR__ . '/.fiz', $config->getDotenvPaths()['.fiz']->getPath());
        self::assertEquals(__DIR__ . '/.baz', $config->getDotenvPaths()['.baz']->getPath());
    }

    public function test_it_loads_dotenv_files_from_environments_overwritten(): void
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [],
            'dotenv' => [
                '.fiz',
                '.baz',
            ],
            'environments' => [
                'env' => [
                    'dotenv' => [
                        '_foo/.fiz',
                        '_foo/.buz',
                    ],
            ], ],
        ]);

        $loader = $this->createConfigLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt', []);

        self::assertCount(3, $config->getDotenvPaths('env'));
        self::assertEquals(__DIR__ . '/_foo/.fiz', $config->getDotenvPaths('env')['.fiz']->getPath());
        self::assertEquals(__DIR__ . '/.baz', $config->getDotenvPaths('env')['.baz']->getPath());
        self::assertEquals(__DIR__ . '/_foo/.buz', $config->getDotenvPaths('env')['.buz']->getPath());
    }

    public function test_fixPath_throws_exception(): void
    {
        $loader = $this->createConfigLoader();

        $method = new ReflectionMethod(YamlConfigFileLoader::class, 'fixPath');
        $method->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);

        $method->invoke($loader, __DIR__, 'absoluteOrRelativePath', 'baseFile');
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
}
