<?php declare (strict_types=1);


namespace Shopware\Psh\Test\Acceptance;

use Shopware\Psh\Application\Application;

class MinApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function test_application_listing()
    {
        $application = new Application(__DIR__ . '/_minApp');
        MockWriter::addToApplication($application);

        $application->run([]);

        $this->assertNotFalse(strpos(MockWriter::$content, '1 script(s) available'));
    }
}
