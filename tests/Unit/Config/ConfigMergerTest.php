<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Config;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Config\Config;
use Shopware\Psh\Config\ConfigEnvironment;
use Shopware\Psh\Config\ConfigMerger;
use Shopware\Psh\Config\EnvironmentResolver;
use Shopware\Psh\Config\ScriptsPath;
use Shopware\Psh\Config\SimpleValueProvider;

class ConfigMergerTest extends TestCase
{
    const DEFAULT_ENV = 'env';

    public function test_it_can_be_created(): void
    {
        self::assertInstanceOf(ConfigMerger::class, new ConfigMerger());
    }

    public function test_it_should_return_config(): void
    {
        $config = new Config(new EnvironmentResolver(), '', [], [], 'my header');

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config);

        self::assertInstanceOf(Config::class, $config);
        self::assertEquals($config, $result);
    }

    public function test_it_should_override_header(): void
    {
        $config = new Config(new EnvironmentResolver(), '', [], [], 'my header');
        $override = new Config(new EnvironmentResolver(), '', [], [], 'override');

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $override);

        self::assertInstanceOf(Config::class, $result);
        self::assertEquals('override', $result->getHeader());
    }

    public function test_it_should_override_default_environment(): void
    {
        $config = new Config(new EnvironmentResolver(), 'default env', [], []);
        $override = new Config(new EnvironmentResolver(), 'default env override', [], []);

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $override);

        self::assertInstanceOf(Config::class, $result);
        self::assertEquals('default env override', $result->getDefaultEnvironment());
    }

    public function test_it_should_use_original_config_if_override_is_empty(): void
    {
        $config = new Config(new EnvironmentResolver(), 'default env', [self::DEFAULT_ENV => new ConfigEnvironment(false)], [], 'my header');
        $override = new Config(new EnvironmentResolver(), '', [], [], '');

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $override);

        self::assertEquals('my header', $result->getHeader());
        self::assertEquals('default env', $result->getDefaultEnvironment());
        self::assertEquals([self::DEFAULT_ENV => new ConfigEnvironment(false)], $result->getEnvironments());
    }

    public function test_it_should_add_environment_from_override(): void
    {
        $envs = [self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], ['foo' => 'bar'])];
        $newEnv = ['newEnv' => new ConfigEnvironment(false, [])];

        $config = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $envs, []);
        $override = new Config(new EnvironmentResolver(), '', $newEnv, []);

        $merger = new ConfigMerger();
        $mergedConfig = $merger->mergeOverride($config, $override);

        self::assertInstanceOf(Config::class, $mergedConfig);

        self::assertArrayHasKey(self::DEFAULT_ENV, $mergedConfig->getEnvironments());
        self::assertArrayHasKey('newEnv', $mergedConfig->getEnvironments());
        self::assertArrayHasKey('FOO', $mergedConfig->getConstants());
        self::assertContainsOnlyInstancesOf(SimpleValueProvider::class, $mergedConfig->getConstants());
    }

    public function test_it_should_use_original_environments(): void
    {
        $envs = [self::DEFAULT_ENV => new ConfigEnvironment(false, [new ScriptsPath('actions', '', false)])];

        $config = new Config(new EnvironmentResolver(), '', $envs, []);
        $override = new Config(new EnvironmentResolver(), '', [], []);

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $override);

        self::assertInstanceOf(Config::class, $result);
        self::assertEquals('actions', $result->getAllScriptsPaths()[0]->getPath());
    }

    public function test_it_should_override_environment_paths(): void
    {
        $envs = [self::DEFAULT_ENV => new ConfigEnvironment(false, [new ScriptsPath('actions', '', false)])];

        $overrideEnvs = [self::DEFAULT_ENV => new ConfigEnvironment(false, [new ScriptsPath('override/actions', '', false)])];

        $config = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $envs, []);
        $override = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $override);

        self::assertInstanceOf(Config::class, $result);
        self::assertEquals('override/actions', $result->getAllScriptsPaths()[0]->getPath());
    }

    public function test_it_should_override_environment_dynamic_values(): void
    {
        $envs = [self::DEFAULT_ENV => new ConfigEnvironment(false, [], ['DYNAMIC_VAR' => 'dynamic value'])];

        $overrideEnvs = [self::DEFAULT_ENV => new ConfigEnvironment(false, [], ['DYNAMIC_VAR' => 'dynamic value override'])];

        $config = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $envs, []);
        $override = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $override);

        self::assertArrayHasKey('DYNAMIC_VAR', $result->getDynamicVariables(self::DEFAULT_ENV));
        self::assertSame('dynamic value override', $result->getDynamicVariables(self::DEFAULT_ENV)['DYNAMIC_VAR']->getCommand());
    }

    public function test_it_should_add_dynamic_values(): void
    {
        $envs = [self::DEFAULT_ENV => new ConfigEnvironment(false, [], ['DYNAMIC_VAR' => 'dynamic value', 'DYNAMIC_VAR2' => 'dynamic value 2'])];

        $overrideEnvs = [self::DEFAULT_ENV => new ConfigEnvironment(false, [], ['DYNAMIC_VAR' => 'dynamic value override', 'DYNAMIC_OVERRIDE_VAR' => 'dynamic override value'])];

        $config = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $envs, []);
        $override = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $override);

        self::assertCount(3, $result->getDynamicVariables(self::DEFAULT_ENV));

        self::assertArrayHasKey('DYNAMIC_VAR', $result->getDynamicVariables(self::DEFAULT_ENV));
        self::assertSame('dynamic value override', $result->getDynamicVariables(self::DEFAULT_ENV)['DYNAMIC_VAR']->getCommand());

        self::assertArrayHasKey('DYNAMIC_VAR2', $result->getDynamicVariables(self::DEFAULT_ENV));
        self::assertSame('dynamic value 2', $result->getDynamicVariables(self::DEFAULT_ENV)['DYNAMIC_VAR2']->getCommand());

        self::assertArrayHasKey('DYNAMIC_OVERRIDE_VAR', $result->getDynamicVariables(self::DEFAULT_ENV));
        self::assertSame('dynamic override value', $result->getDynamicVariables(self::DEFAULT_ENV)['DYNAMIC_OVERRIDE_VAR']->getCommand());
    }

    public function test_it_should_add_and_override_constant_values(): void
    {
        $envs = [
            self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [
                'CONST' => 'constant value',
                'ORIGINAL_CONST' => 'original constant value',
            ]),
        ];

        $overrideEnvs = [
            self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [
                'CONST' => 'override constant value',
                'ADDED_CONST' => 'override constant',
            ]),
        ];

        $config = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $envs, []);
        $override = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $override);

        self::assertCount(3, $result->getConstants(self::DEFAULT_ENV));

        self::assertArrayHasKey('CONST', $result->getConstants(self::DEFAULT_ENV));
        self::assertSame('override constant value', $result->getConstants(self::DEFAULT_ENV)['CONST']->getValue());

        self::assertArrayHasKey('ORIGINAL_CONST', $result->getConstants(self::DEFAULT_ENV));
        self::assertSame('original constant value', $result->getConstants(self::DEFAULT_ENV)['ORIGINAL_CONST']->getValue());

        self::assertArrayHasKey('ADDED_CONST', $result->getConstants(self::DEFAULT_ENV));
        self::assertSame('override constant', $result->getConstants(self::DEFAULT_ENV)['ADDED_CONST']->getValue());
    }

    public function test_external_params_overwrite_consts_independant_of_case(): void
    {
        $envs = [
            self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [
                'Const' => 'constant value',
            ]),
        ];

        $overrideEnvs = [
            self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [
                'cONST' => 'override constant value',
            ]),
        ];

        $config = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $envs, ['const' => 'the real value']);
        $override = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $override);

        self::assertCount(1, $result->getConstants(self::DEFAULT_ENV));

        self::assertArrayHasKey('CONST', $result->getConstants(self::DEFAULT_ENV));
        self::assertSame('the real value', $result->getConstants(self::DEFAULT_ENV)['CONST']->getValue());
    }

    public function test_it_should_override_templates(): void
    {
        $envs = [
            self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [], [
                ['source' => '/tmp/template.tpl', 'destination' => '/tmp/template.php'],
            ]),
        ];

        $overrideEnvs = [
            self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [], [
                ['source' => '/tmp/override.tpl', 'destination' => '/tmp/override.php'],
            ]),
        ];

        $config = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $envs, []);
        $overrideConfig = new Config(new EnvironmentResolver(), self::DEFAULT_ENV, $overrideEnvs, []);

        $merger = new ConfigMerger();
        $result = $merger->mergeOverride($config, $overrideConfig);

        self::assertEquals(
            ['source' => '/tmp/override.tpl', 'destination' => '/tmp/override.php'],
            $result->getEnvironments()[self::DEFAULT_ENV]->getTemplates()[0]
        );
    }

    public function test_dotenv_paths(): void
    {
        $configMerge = (new ConfigMerger())->mergeOverride(
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [], [], [
                    '.a' => 'first/.a',
                    '.b' => 'first/.b',
                ]),
            ], []),
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [], [], [
                    '.a' => 'overwrite/.a',
                    '.c' => 'overwrite/.c',
                ]),
            ], [])
        );

        self::assertCount(3, $configMerge->getDotenvPaths());

        $paths = $configMerge->getDotenvPaths();

        self::assertEquals('overwrite/.a', $paths['.a']->getPath());
        self::assertEquals('first/.b', $paths['.b']->getPath());
        self::assertEquals('overwrite/.c', $paths['.c']->getPath());
    }

    public function test_require(): void
    {
        $configMerge = (new ConfigMerger())->mergeOverride(
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [], [], [], ['bar' => 'necessity']),
            ], []),
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(false, [], [], [], [], [], ['foo' => 'important']),
            ], [])
        );

        self::assertCount(2, $configMerge->getRequiredVariables());

        self::assertSame('BAR', $configMerge->getRequiredVariables()['BAR']->getName());
        self::assertSame('FOO', $configMerge->getRequiredVariables()['FOO']->getName());
    }

    public function test_hidden_override_with_both_false(): void
    {
        $configMerge = (new ConfigMerger())->mergeOverride(
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(false),
            ], []),
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(false),
            ], [])
        );

        self::assertFalse($configMerge->getEnvironments()[$configMerge->getDefaultEnvironment()]->isHidden());
    }

    public function test_hidden_override_with_hidden_base(): void
    {
        $configMerge = (new ConfigMerger())->mergeOverride(
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(true),
            ], []),
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(false),
            ], [])
        );

        self::assertTrue($configMerge->getEnvironments()[$configMerge->getDefaultEnvironment()]->isHidden());
    }

    public function test_hidden_override_with_hidden_override(): void
    {
        $configMerge = (new ConfigMerger())->mergeOverride(
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(false),
            ], []),
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(true),
            ], [])
        );

        self::assertTrue($configMerge->getEnvironments()[$configMerge->getDefaultEnvironment()]->isHidden());
    }

    public function test_merge_override_with_a_single_argument_just_returns_it(): void
    {
        $config = new Config(new EnvironmentResolver(), '', [], []);

        $mergedConfig = (new ConfigMerger())->mergeImport($config);

        self::assertSame($config, $mergedConfig);
    }

    public function test_merge_import_shows_a_different_behaviour(): void
    {
        $configMerge = (new ConfigMerger())->mergeImport(
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(false, [new ScriptsPath('foo', '', false)], [], [], [['source' => 'baz', 'destination' => 'buz']], [], ['bar' => 'I need this']),
            ], []),
            new Config(new EnvironmentResolver(), self::DEFAULT_ENV, [
                self::DEFAULT_ENV => new ConfigEnvironment(true, [new ScriptsPath('bar', '', false)], [], [], [['source' => 'biz', 'destination' => 'bez']]),
            ], [])
        );

        self::assertSame(
            ['foo', 'bar'],
            [
                $configMerge->getEnvironments()[$configMerge->getDefaultEnvironment()]->getAllScriptsPaths()[0]->getPath(),
                $configMerge->getEnvironments()[$configMerge->getDefaultEnvironment()]->getAllScriptsPaths()[1]->getPath(),
            ]
        );
        self::assertSame(
            [['source' => 'baz', 'destination' => 'buz'], ['source' => 'biz', 'destination' => 'bez']],
            $configMerge->getEnvironments()[$configMerge->getDefaultEnvironment()]->getTemplates()
        );
        self::assertSame('BAR', $configMerge->getRequiredVariables()['BAR']->getName());
    }
}
