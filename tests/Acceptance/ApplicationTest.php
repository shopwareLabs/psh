<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Acceptance;

use Shopware\Psh\Application\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @befoe
     * @after
     */
    public function clearCreatedResults()
    {
        @unlink(__DIR__ . '/_app/result.txt');
    }

    public function test_application_listing()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run([]);

        $this->assertEquals(0, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, '3 script(s) available'));
    }

    public function test_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        $this->assertEquals(0, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, 'ls -al'));
        $this->assertNotFalse(strpos(MockWriter::$content, '(1/3) Starting'));
        $this->assertNotFalse(strpos(MockWriter::$content, '(2/3) Starting'));
        $this->assertNotFalse(strpos(MockWriter::$content, '(3/3) Starting'));
        $this->assertNotFalse(strpos(MockWriter::$content, ' echo "prod"'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'All commands successfully executed!'));
        self::assertStringEqualsFile(__DIR__ . '/_app/result.txt', 'prod');
    }

    public function test_environment_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);


        $exitCode = $application->run(['', 'test:env']);

        $this->assertEquals(0, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, 'ls -al'), 'ls -al');
        $this->assertNotFalse(strpos(MockWriter::$content, '(1/3) Starting'), '(1/3) Starting');
        $this->assertNotFalse(strpos(MockWriter::$content, '(2/3) Starting'), '(2/3) Starting');
        $this->assertNotFalse(strpos(MockWriter::$content, '(3/3) Starting'), '(3/3) Starting');
        $this->assertNotFalse(strpos(MockWriter::$content, ' echo "test"'), ' echo "test"');
        $this->assertNotFalse(strpos(MockWriter::$content, 'All commands successfully executed!'), 'All commands successfully executed!');
        self::assertStringEqualsFile(__DIR__ . '/_app/result.txt', 'test');
    }

    public function test_error_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'error']);
        $this->assertEquals(Application::RESULT_ERROR, $exitCode);
        $this->assertNotEquals(0, $exitCode);
    }
}
