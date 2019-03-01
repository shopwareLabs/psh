<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Acceptance;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Application\Application;
use function strpos;

class ApplicationActionNotFoundTest extends TestCase
{
    public function test_application_execution()
    {
        $application = new Application(__DIR__ . '/_app_action_not_found');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test']);

        static::assertEquals(1, $exitCode);
        static::assertContains('Script with name test2 not found', MockWriter::$content);
        static::assertContains('Using .psh.yml', MockWriter::$content);
    }
}
