<?php declare (strict_types=1);


namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\ScriptRuntime\CommandBuilder;

class CommandBuilderTest extends TestCase
{
    public function test_set_ignore_error()
    {
        $reflection = new \ReflectionClass('\Shopware\Psh\ScriptRuntime\CommandBuilder');

        $ignoreError = $reflection->getProperty('ignoreError');
        $ignoreError->setAccessible(true);

        $commandBuilder = new CommandBuilder();

        self::assertNull($ignoreError->getValue($commandBuilder));

        $commandBuilder->setIgnoreError();

        self::assertTrue($ignoreError->getValue($commandBuilder));
    }
}
