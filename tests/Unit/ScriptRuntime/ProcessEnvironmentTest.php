<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use Shopware\Psh\ScriptRuntime\ProcessEnvironment;
use Shopware\Psh\ScriptRuntime\SimpleValueProvider;
use Shopware\Psh\ScriptRuntime\Template;
use Shopware\Psh\ScriptRuntime\ValueProvider;
use Symfony\Component\Process\Process;

class ProcessEnvironmentTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_returns_all_passed_constants()
    {
        $env = new ProcessEnvironment(['FOO' => 'BAR'], [], []);
        $this->assertEquals(['FOO' => new SimpleValueProvider('BAR')], $env->getAllValues());
    }

    public function test_it_creates_processes()
    {
        $env = new ProcessEnvironment([], [], []);
        $this->assertInstanceOf(Process::class, $env->createProcess('foo'));
        $this->assertEquals('foo', $env->createProcess('foo')->getCommandLine());
    }

    public function test_it_resolves_variables()
    {
        $env = new ProcessEnvironment([], [
            'FOO' => 'ls',
            'BAR' => 'echo "HEY"'
        ], []);

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
        ]);

        $templates = $env->getTemplates();

        $this->assertContainsOnlyInstancesOf(Template::class, $templates);
        $this->assertCount(1, $templates);
    }
}
