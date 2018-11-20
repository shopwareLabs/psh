<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigEnvironment;
use Shopware\Psh\Config\ConfigMerger;

class ConfigMergerTest extends TestCase
{
    const DEFAULT_ENV = 'env';

    public function test_it_can_be_created()
    {
        $this->assertInstanceOf(ConfigMerger::class, new ConfigMerger());
    }

    public function test_it_should_return_config()
    {
        $config = new Config('my header', '', [], []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config);

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals($config, $result);
    }

    public function test_it_should_override_header()
    {
        $config = new Config('my header', '', [], []);
        $override = new Config('override', '', [], []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertInstanceOf(Config::class, $result);
        $this->assertEquals('override', $result->getHeader());
    }

    public function test_it_should_override_default_environment()
    {
        $config = new Config('', 'default env', [], []);
        $override = new Config('', 'default env override', [], []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertInstanceOf(Config::class, $result);
        $this->assertEquals('default env override', $result->getDefaultEnvironment());
    }

    public function test_it_should_use_original_config_if_override_is_empty()
    {
        $config = new Config('my header', 'default env', [self::DEFAULT_ENV => new ConfigEnvironment()], []);
        $override = new Config('', '', [], []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertEquals('my header', $result->getHeader());
        $this->assertEquals('default env', $result->getDefaultEnvironment());
        $this->assertEquals([self::DEFAULT_ENV => new ConfigEnvironment()], $result->getEnvironments());
    }

    public function test_it_should_use_original_environments()
    {
        $envs = [self::DEFAULT_ENV => new ConfigEnvironment(['actions'])];

        $config = new Config('', '', $envs, []);
        $override = new Config('', '', [], []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertInstanceOf(Config::class, $result);
        $this->assertEquals('actions', $result->getAllScriptPaths()[0]->getPath());
    }

    public function test_it_should_override_environment_paths()
    {
        $envs = [self::DEFAULT_ENV => new ConfigEnvironment(['actions'])];

        $overrideEnvs = [self::DEFAULT_ENV => new ConfigEnvironment(['override/actions'])];

        $config = new Config('', self::DEFAULT_ENV, $envs, []);
        $override = new Config('', self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertInstanceOf(Config::class, $result);
        $this->assertEquals('override/actions', $result->getAllScriptPaths()[0]->getPath());
    }

    public function test_it_should_override_environment_dynamic_values()
    {
        $envs = [self::DEFAULT_ENV => new ConfigEnvironment([], ['DYNAMIC_VAR' => 'dynamic value'])];

        $overrideEnvs = [self::DEFAULT_ENV => new ConfigEnvironment([], ['DYNAMIC_VAR' => 'dynamic value override'])];

        $config = new Config('', self::DEFAULT_ENV, $envs, []);
        $override = new Config('', self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertEquals([
            'DYNAMIC_VAR' => 'dynamic value override'
        ], $result->getDynamicVariables(self::DEFAULT_ENV));
    }

    public function test_it_should_add_dynamic_values()
    {
        $envs = [self::DEFAULT_ENV => new ConfigEnvironment([], ['DYNAMIC_VAR' => 'dynamic value', 'DYNAMIC_VAR2' => 'dynamic value 2'])];

        $overrideEnvs = [self::DEFAULT_ENV => new ConfigEnvironment([], ['DYNAMIC_VAR' => 'dynamic value override', 'DYNAMIC_OVERRIDE_VAR' => 'dynamic override value'])];

        $config = new Config('', self::DEFAULT_ENV, $envs, []);
        $override = new Config('', self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertEquals([
            'DYNAMIC_VAR' => 'dynamic value override',
            'DYNAMIC_VAR2' => 'dynamic value 2',
            'DYNAMIC_OVERRIDE_VAR' => 'dynamic override value'
        ], $result->getDynamicVariables(self::DEFAULT_ENV));
    }

    public function test_it_should_add_and_override_constant_values()
    {
        $envs = [
            self::DEFAULT_ENV => new ConfigEnvironment([], [], [
                'CONST' => 'constant value',
                'ORIGINAL_CONST' => 'original constant value'
            ])
        ];

        $overrideEnvs = [
            self::DEFAULT_ENV => new ConfigEnvironment([], [], [
                'CONST' => 'override constant value',
                'ADDED_CONST' => 'override constant'
            ])
        ];

        $config = new Config('', self::DEFAULT_ENV, $envs, []);
        $override = new Config('', self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $override);

        $this->assertEquals([
            'CONST' => 'override constant value',
            'ORIGINAL_CONST' => 'original constant value',
            'ADDED_CONST' => 'override constant'
        ], $result->getConstants(self::DEFAULT_ENV));
    }

    public function test_it_should_override_templates()
    {
        $envs = [
            self::DEFAULT_ENV => new ConfigEnvironment([], [], [], [
                [ 'source' => '/tmp/template.tpl', 'destination' => '/tmp/template.php' ]
            ])
        ];

        $overrideEnvs = [
            self::DEFAULT_ENV => new ConfigEnvironment([], [], [], [
                [ 'source' => '/tmp/override.tpl', 'destination' => '/tmp/override.php' ]
            ])
        ];

        $config = new Config('', self::DEFAULT_ENV, $envs, []);
        $overrideConfig = new Config('', self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->merge($config, $overrideConfig);

        $this->assertEquals(
            [ 'source' => '/tmp/override.tpl', 'destination' => '/tmp/override.php' ],
            $result->getEnvironments()[self::DEFAULT_ENV]->getTemplates()[0]
        );
    }
}
