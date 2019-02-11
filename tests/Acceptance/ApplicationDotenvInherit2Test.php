<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Acceptance;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Psh\Application\Application;
use function strpos;

class ApplicationDotenvInherit2Test extends TestCase
{
    public function test_application_execution()
    {
        $application = new Application(__DIR__ . '/_app_dotenv_inherit2');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test']);

        static::assertEquals(0, $exitCode);
        static::assertContains('Using .psh.yml', MockWriter::$content);
        static::assertContains('(1/1) Starting', MockWriter::$content);
        static::assertContains(' echo test2', MockWriter::$content);
        static::assertContains('test2', MockWriter::$content);
        static::assertContains('All commands successfully executed!', MockWriter::$content);
        static::assertContains('Duration:', MockWriter::$content);
    }

    public function test_application_execution2()
    {
        $application = new Application(__DIR__ . '/_app_dotenv_inherit2');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test:test']);

        static::assertEquals(0, $exitCode);
        static::assertContains('Using .psh.yml', MockWriter::$content);
        static::assertContains('(1/1) Starting', MockWriter::$content);
        static::assertContains(' echo test3', MockWriter::$content);
        static::assertContains('test3', MockWriter::$content);
        static::assertContains('All commands successfully executed!', MockWriter::$content);
        static::assertContains('Duration:', MockWriter::$content);
    }
}
