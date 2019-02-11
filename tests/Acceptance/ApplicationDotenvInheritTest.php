<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Acceptance;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\Environment\Runtime;
use Shopware\Psh\Application\Application;

class ApplicationDotenvInheritTest extends TestCase
{
    public function test_application_execution()
    {
        $application = new Application(__DIR__ . '/_app_dotenv_inherit');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test:test']);

        static::assertEquals(0, $exitCode);
        static::assertContains('Using .psh.yml', MockWriter::$content);
        static::assertContains('(1/1) Starting', MockWriter::$content);
        static::assertContains(' echo test', MockWriter::$content);
        static::assertContains('All commands successfully executed!', MockWriter::$content);
        static::assertContains('Duration:', MockWriter::$content);
    }
}
