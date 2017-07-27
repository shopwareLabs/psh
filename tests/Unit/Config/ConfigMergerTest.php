<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Application\ApplicationFactory;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigEnvironment;
use Shopware\Psh\Config\ConfigMerger;

class ConfigMergerTest extends TestCase
{
    public function test_it_can_be_created()
    {
        $this->assertInstanceOf(ConfigMerger::class, new ConfigMerger());
    }

    public function test_it_should_return_config()
    {
        $config = new Config('my header', '', []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config);

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals($config, $result);
    }

    public function test_it_should_override_header()
    {
        $config = new Config('my header', '', []);
        $override = new Config('override', '', []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertInstanceOf(Config::class, $result);
        $this->assertEquals('override', $result->getHeader());
    }

    public function test_it_should_override_default_environment()
    {
        $config = new Config('', 'default env', []);
        $override = new Config('', 'default env override', []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertInstanceOf(Config::class, $result);
        $this->assertEquals('default env override', $result->getDefaultEnvironment());
    }

    public function test_it_should_use_original_config_if_override_is_empty()
    {
        $config = new Config('my header', 'default env', [ 'env' => new ConfigEnvironment() ]);
        $override = new Config('', '', []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertEquals('my header', $result->getHeader());
        $this->assertEquals('default env', $result->getDefaultEnvironment());
        $this->assertEquals([ 'env' => new ConfigEnvironment() ], $result->getEnvironments());
    }

    public function test_it_should_use_original_environments()
    {
        $environments = [ 'env' => new ConfigEnvironment([ 'actions' ]) ];

        $config = new Config('', '', $environments);
        $override = new Config('', '', []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertInstanceOf(Config::class, $result);
        $this->assertEquals('actions', $result->getAllScriptPaths()[0]->getPath());
    }
}