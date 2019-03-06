<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use Shopware\Psh\Config\ScriptPath;
use Shopware\Psh\ScriptRuntime\ProcessEnvironment;
use Shopware\Psh\ScriptRuntime\SimpleValueProvider;
use Shopware\Psh\ScriptRuntime\Template;
use Shopware\Psh\ScriptRuntime\ValueProvider;
use Symfony\Component\Process\Process;

class ProcessEnvironmentTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        putenv('FOO');
    }


    public function test_it_returns_all_passed_constants()
    {
        $env = new ProcessEnvironment(['FOO' => 'BAR'], [], [], []);
        $this->assertEquals(['FOO' => new SimpleValueProvider('BAR')], $env->getAllValues());
    }

    public function test_it_creates_processes()
    {
        $env = new ProcessEnvironment([], [], [], []);
        $this->assertInstanceOf(Process::class, $env->createProcess('foo'));
        $this->assertEquals('foo', $env->createProcess('foo')->getCommandLine());
    }

    public function test_it_resolves_variables()
    {
        $env = new ProcessEnvironment([], [
            'FOO' => 'ls',
            'BAR' => 'echo "HEY"'
        ], [], []);

        $resolvedValues = $env->getAllValues();

        $this->assertContainsOnlyInstancesOf(ValueProvider::class, $resolvedValues);
        $this->assertCount(2, $resolvedValues);

        foreach ($resolvedValues as $value) {
            $this->assertEquals(trim($value->getValue()), $value->getValue());
        }
    }

    public function test_it_creates_templates()
    {
        $env = new ProcessEnvironment([], [], [
            ['source' => __DIR__ . '_foo.tpl', 'destination' => 'bar.txt']
        ], []);

        $templates = $env->getTemplates();

        $this->assertContainsOnlyInstancesOf(Template::class, $templates);
        $this->assertCount(1, $templates);
    }

    public function test_dotenv_can_be_overwritten_by_existing_env_vars()
    {
        putenv('FOO=baz');
        $env = new ProcessEnvironment([], [], [], [
            new ScriptPath(__DIR__ . '/_dotenv/simple.env')
        ]);

        $this->assertSame('baz', $env->getAllValues()['FOO']->getValue());
    }

    public function test_dotenv_file_variables()
    {
        $env = new ProcessEnvironment([], [], [], [
            new ScriptPath(__DIR__ . '/_dotenv/simple.env')
        ]);

        $this->assertCount(1, $env->getAllValues());
        $this->assertSame('bar', $env->getAllValues()['FOO']->getValue());
    }
}
