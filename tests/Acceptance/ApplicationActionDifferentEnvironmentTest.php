<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Acceptance;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Application\Application;

class ApplicationActionDifferentEnvironmentTest extends TestCase
{
    public function test_application_execution()
    {
        $application = new Application(__DIR__ . '/_app_action_different_environment');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'different:test2']);

        static::assertEquals(0, $exitCode);
        static::assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yml'));
        static::assertNotFalse(strpos(MockWriter::$content, '(1/1) Starting'));
        static::assertNotFalse(strpos(MockWriter::$content, ' echo "Test2"'));
        static::assertNotFalse(strpos(MockWriter::$content, 'All commands successfully executed!'));
        static::assertNotFalse(strpos(MockWriter::$content, 'Duration:'));
    }
}
