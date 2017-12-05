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
        $this->assertNotFalse(
            strpos(MockWriter::$content, 'All commands successfully executed!'), 'All commands successfully executed!'
        );
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
        $this->assertNotFalse(
            strpos(MockWriter::$content, 'All commands successfully executed!'), 'All commands successfully executed!'
        );
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
        $this->assertFalse(
            strpos(MockWriter::$content, 'All commands successfully executed!'), 'All commands successfully executed!'
        );
        $this->assertFalse(strpos(MockWriter::$content, '3 script(s) available'));
    }

    public function test_psh_config_override_should_override_existing_psh_configuration()
    {
        $application = new Application(__DIR__ . '/_override_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run([]);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yaml extended by .psh.yaml.override'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'override'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'override-app'));
    }

    public function test_psh_config_override_should_override_existing_psh_configuration_executes_with_override_params()
    {
        $application = new Application(__DIR__ . '/_override_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'override-app', '--external_param', 'foo-content']);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);
        $this->assertNotFalse(strpos(MockWriter::$content, 'Using .psh.yaml extended by .psh.yaml.override'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'override'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'override-app'));
        $this->assertFalse(strpos(MockWriter::$content, '_EXTERNAL_PARAM_'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'foo-content'));
    }

    public function test_psh_groups_commands_by_environment()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['']);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);

        $this->assertNotFalse(strstr(MockWriter::$content, 'Using .psh.yml'));
        $this->assertNotFalse(strstr(MockWriter::$content, 'default:'));
        $this->assertNotFalse(strstr(MockWriter::$content, 'test:'));
    }

    public function test_bash_autocomplete_listing()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'bash_autocompletion_dump']);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);

        $this->assertFalse(strstr(MockWriter::$content, 'Using .psh.yml'));
        $this->assertNotFalse(strstr(MockWriter::$content, 'error simple test:env test:env2'));
    }

    public function test_script_not_found_listing_with_guess()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'sümple']);

        $this->assertEquals(Application::RESULT_ERROR, $exitCode);

        $this->assertNotFalse(strstr(MockWriter::$content, 'Using .psh.yml'));
        $this->assertNotFalse(strstr(MockWriter::$content, 'Have you been looking for this?'));
        $this->assertNotFalse(strstr(MockWriter::$content, '- simple'));
        $this->assertNotFalse(strstr(MockWriter::$content, '1 script(s) available'));
    }

    public function test_script_not_found_listing_without_guess()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'pkdi']);

        $this->assertEquals(Application::RESULT_ERROR, $exitCode);

        $this->assertNotFalse(strstr(MockWriter::$content, 'Using .psh.yml'));
        $this->assertNotFalse(strstr(MockWriter::$content, 'Script with name pkdi not found'));
    }

    public function test_showListings_returns_no_scripts_available()
    {
        $application = new Application(__DIR__);
        MockWriter::addToApplication($application);

        $application->showListing([]);

        $this->assertNotFalse(strstr(MockWriter::$content, 'Currently no scripts available'));
    }

    public function test_it_throws_exception_InvalidArgumentException_and_it_is_catched()
    {
        $application = new Application(__DIR__.'/_app_invalid_template');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        $this->assertEquals(Application::RESULT_ERROR, $exitCode);
    }

    public function test_it_throws_exception_InvalidParameterException_and_it_is_catched()
    {
        $application = new Application(__DIR__.'/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple', '-param']);

        $this->assertEquals(Application::RESULT_ERROR, $exitCode);
    }
}
