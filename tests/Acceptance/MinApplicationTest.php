<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Acceptance;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Application\Application;
use function mb_strpos;

class MinApplicationTest extends TestCase
{
    public function test_application_listing(): void
    {
        $application = new Application(__DIR__ . '/_minApp');
        MockWriter::addToApplication($application);

        $application->run([]);

        self::assertNotFalse(mb_strpos(MockWriter::$content, '1 script(s) available'));
    }
}
