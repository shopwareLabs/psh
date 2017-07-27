<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Config\Config;
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
}