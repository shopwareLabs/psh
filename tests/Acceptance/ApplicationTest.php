<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Acceptance;

use Shopware\Psh\Application\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @before
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
        $this->assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yml'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'test:env'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'test:env2'));
        $this->assertNotFalse(strpos(MockWriter::$content, '4 script(s) available'));
        $this->assertFalse(strpos(MockWriter::$content, 'Duration:'));
    }

    public function test_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        $this->assertEquals(0, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, 'ls -al'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yml'));
        $this->assertNotFalse(strpos(MockWriter::$content, '(1/3) Starting'));
        $this->assertNotFalse(strpos(MockWriter::$content, '(2/3) Starting'));
        $this->assertNotFalse(strpos(MockWriter::$content, '(3/3) Starting'));
        $this->assertNotFalse(strpos(MockWriter::$content, ' echo "prod"'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'All commands successfully executed!'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'Duration:'));
        self::assertStringEqualsFile(__DIR__ . '/_app/result.txt', 'prod');
    }

    public function test_environment_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);


        $exitCode = $application->run(['', 'test:env']);

        $this->assertEquals(0, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, 'ls -al'), 'ls -al');
        $this->assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yml'), 'Using .psh.yml');
        $this->assertNotFalse(strpos(MockWriter::$content, '(1/3) Starting'), '(1/3) Starting');
        $this->assertNotFalse(strpos(MockWriter::$content, '(2/3) Starting'), '(2/3) Starting');
        $this->assertNotFalse(strpos(MockWriter::$content, '(3/3) Starting'), '(3/3) Starting');
        $this->assertNotFalse(strpos(MockWriter::$content, ' echo "test"'), ' echo "test"');
        $this->assertNotFalse(strpos(MockWriter::$content, 'All commands successfully executed!'), 'All commands successfully executed!');
        $this->assertNotFalse(strpos(MockWriter::$content, 'Duration:'));
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

    public function test_chain_two_commands_with_a_comma_executes_both()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'simple,test:env']);

        $this->assertEquals(0, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yml'));
        $this->assertNotFalse(strpos(MockWriter::$content, ' echo "prod"'));
        $this->assertNotFalse(strpos(MockWriter::$content, ' echo "test"'), ' echo "test"');
        $this->assertNotFalse(strpos(MockWriter::$content, 'All commands successfully executed!'), 'All commands successfully executed!');
        $this->assertFalse(strpos(MockWriter::$content, '3 script(s) available'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'Duration:'));
        self::assertStringEqualsFile(__DIR__ . '/_app/result.txt', 'test');
    }

    public function test_chain_two_commands_with_a_comma_executes_both_unless_an_error_occures()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'error,test:env']);

        $this->assertEquals(Application::RESULT_ERROR, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yml'), 'Using .psh.yml');
        $this->assertFalse(strpos(MockWriter::$content, ' echo "test"'), ' echo "test"');
        $this->assertFalse(strpos(MockWriter::$content, 'All commands successfully executed!'), 'All commands successfully executed!');
        $this->assertFalse(strpos(MockWriter::$content, '3 script(s) available'));
    }

    public function test_psh_config_override_should_override_existing_psh_configuration()
    {
        $application = new Application(__DIR__ . '/_overrde_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['test']);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yaml extended by .psh.yaml.override'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'override'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'test'));
    }

    public function test_psh_groups_commands_by_environment()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['']);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);

        $this->assertNotFalse(strstr(MockWriter::$content, "Using .psh.yml"));
        $this->assertNotFalse(strstr(MockWriter::$content, "default:"));
        $this->assertNotFalse(strstr(MockWriter::$content, "test:"));
    }
}
