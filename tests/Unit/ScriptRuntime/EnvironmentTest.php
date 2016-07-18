<?php declare (strict_types = 1);


namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use Shopware\Psh\ScriptRuntime\Environment;
use Shopware\Psh\ScriptRuntime\SimpleValueProvider;
use Shopware\Psh\ScriptRuntime\ValueProvider;
use Symfony\Component\Process\Process;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_returns_all_passed_constants()
    {
        $env = new Environment(['FOO' => 'BAR'], []);
        $this->assertEquals(['FOO' => new SimpleValueProvider('BAR')], $env->getAllValues());
    }

    public function test_it_creates_processes()
    {
        $env = new Environment([], []);
        $this->assertInstanceOf(Process::class, $env->createProcess('foo'));
        $this->assertEquals('foo', $env->createProcess('foo')->getCommandLine());
    }

    public function test_it_resolves_variables()
    {
        $env = new Environment([], [
            'FOO' => 'ls',
            'BAR' => 'echo "HEY"'
        ]);

        $resolvedValues = $env->getAllValues();

        $this->assertContainsOnlyInstancesOf(ValueProvider::class, $resolvedValues);
        $this->assertCount(2, $resolvedValues);

        foreach ($resolvedValues as $value) {
            $this->assertEquals(trim($value->getValue()), $value->getValue());
        }
    }
}
