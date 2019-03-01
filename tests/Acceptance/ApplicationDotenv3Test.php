<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Acceptance;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Psh\Application\Application;
use function strpos;

class ApplicationDotenv3Test extends TestCase
{
    public function test_application_execution()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required value for "TEST"');

        $application = new Application(__DIR__ . '/_app_dotenv3');
        MockWriter::addToApplication($application);

        $application->run(['', 'test']);
    }

    public function test_application_execution2()
    {
        $application = new Application(__DIR__ . '/_app_dotenv3');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test:test']);

        static::assertEquals(0, $exitCode);
        static::assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yml'));
        static::assertNotFalse(strpos(MockWriter::$content, '(1/1) Starting'));
        static::assertNotFalse(strpos(MockWriter::$content, ' echo test2'));
        static::assertNotFalse(strpos(MockWriter::$content, 'test2'));
        static::assertNotFalse(strpos(MockWriter::$content, 'All commands successfully executed!'));
        static::assertNotFalse(strpos(MockWriter::$content, 'Duration:'));
    }
}
