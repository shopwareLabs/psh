<?php declare(strict_types = 1);


namespace Shopware\Psh\Test\Unit\Config;


use Prophecy\Argument;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigLoader;
use Shopware\Psh\Config\YamlConfigFileLoader;
use Symfony\Component\Yaml\Parser;

class YamlConfigFileLoaderTest extends \PHPUnit_Framework_TestCase
{

    public function test_it_can_be_instantiated()
    {
        $loader = new YamlConfigFileLoader(new Parser());
        $this->assertInstanceOf(YamlConfigFileLoader::class, $loader);
        $this->assertInstanceOf(ConfigLoader::class, $loader);
    }

    public function test_it_supports_yaml_files()
    {
        $loader = new YamlConfigFileLoader(new Parser());

        $this->assertTrue($loader->isSupported('fo.yaml'));
        $this->assertTrue($loader->isSupported('fo.yml'));

        $this->assertFalse($loader->isSupported('fo.txt'));
        $this->assertFalse($loader->isSupported('fo.yaml.dist'));

    }

    public function test_it_throws_a_exception_if_no_paths_are_present()
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

        $loader = new YamlConfigFileLoader($yamlMock->reveal());

        $this->setExpectedException(\InvalidArgumentException::class);
        $loader->load(__DIR__ . '/_test.txt');
    }

    public function test_it_throws_a_exception_if_no_dynamics_are_present()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/foo',
                __DIR__ . '/bar',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);

        $loader = new YamlConfigFileLoader($yamlMock->reveal());

        $this->setExpectedException(\InvalidArgumentException::class);
        $loader->load(__DIR__ . '/_test.txt');
    }

    public function test_it_throws_a_exception_if_no_consts_are_present()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/foo',
                __DIR__ . '/bar',
            ],
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
        ]);

        $loader = new YamlConfigFileLoader($yamlMock->reveal());

        $this->setExpectedException(\InvalidArgumentException::class);
        $loader->load(__DIR__ . '/_test.txt');
    }

    public function test_it_creates_a_valid_config_file_if_all_required_params_are_present()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'paths' => [
                __DIR__ . '/foo',
                __DIR__ . '/bar',
            ],
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);


        $loader = new YamlConfigFileLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt');

        $this->assertInstanceOf(Config::class, $config);
    }

    public function test_it_creates_a_valid_config_file_if_all_params_are_present()
    {
        $yamlMock = $this->prophesize(Parser::class);
        $yamlMock->parse('foo')->willReturn([
            'header' => 'foo',
            'paths' => [
                __DIR__ . '/foo',
                __DIR__ . '/bar',
            ],
            'dynamic' => [
                'filesystem' => 'ls -al',
            ],
            'const' => [
                'FOO' => 'bar',
            ],
        ]);

        $loader = new YamlConfigFileLoader($yamlMock->reveal());
        $config = $loader->load(__DIR__ . '/_test.txt');

        $this->assertInstanceOf(Config::class, $config);
    }

}