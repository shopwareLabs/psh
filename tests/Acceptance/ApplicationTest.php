<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Acceptance;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Application\Application;
use function unlink;

class ApplicationTest extends TestCase
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

        $this->assertNoErrorExitCode($exitCode);
        $this->assertContains('Using .psh.xml', MockWriter::$content);
        $this->assertContains('test:env', MockWriter::$content);
        $this->assertContains('test:env2', MockWriter::$content);
        $this->assertContains('6 script(s) available', MockWriter::$content);
        $this->assertNotContains('Duration:', MockWriter::$content);
        $this->assertNotContains('test:.hidden', MockWriter::$content);
    }

    public function test_hidden_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test:.hidden']);

        $this->assertSimpleScript($exitCode, 'test');
    }

    public function test_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        $this->assertSimpleScript($exitCode, 'prod');
    }

    public function test_environment_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test:env']);

        $this->assertSimpleScript($exitCode, 'test');
    }

    public function test_hidden_environment_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'hidden:env']);

        $this->assertSimpleScript($exitCode, 'hidden');
    }

    public function test_environment_deferred_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test:deferred']);

        $this->assertNoErrorExitCode($exitCode);
        $this->assertContains('Using .psh.xml', MockWriter::$content);
        $this->assertContains('(1/3) Deferring', MockWriter::$content);
        $this->assertContains('(2/3) Waiting', MockWriter::$content);
        $this->assertContains('WAITING...', MockWriter::$content);
        $this->assertContains('(1/1) Output from', MockWriter::$content);
        $this->assertNotContains('echo "__ENV__"', MockWriter::$content);
        $this->assertContains('(3/3) Deferring', MockWriter::$content);
        $this->assertContains(' echo "test"', MockWriter::$content);
        $this->assertContains('All commands successfully executed!', MockWriter::$content);
        $this->assertContains('Duration:', MockWriter::$content);
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

        $this->assertNoErrorExitCode($exitCode);
        $this->assertContains('Using .psh.xml', MockWriter::$content);
        $this->assertContains(' echo "prod"', MockWriter::$content);
        $this->assertContains(' echo "test"', MockWriter::$content);
        $this->assertContains('All commands successfully executed!', MockWriter::$content);
        $this->assertNotContains('3 script(s) available', MockWriter::$content);
        $this->assertContains('Duration:', MockWriter::$content);
        self::assertStringEqualsFile(__DIR__ . '/_app/result.txt', 'test');
    }

    public function test_chain_two_commands_with_a_comma_executes_both_unless_an_error_occures()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'error,test:env']);

        $this->assertEquals(Application::RESULT_ERROR, $exitCode);
        $this->assertContains('Using .psh.xml', MockWriter::$content);
        $this->assertNotContains(' echo "test"', MockWriter::$content);
        $this->assertNotContains('All commands successfully executed!', MockWriter::$content);
        $this->assertNotContains('3 script(s) available', MockWriter::$content);
    }

    public function test_psh_config_override_should_override_existing_psh_configuration()
    {
        $application = new Application(__DIR__ . '/_override_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run([]);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);
        $this->assertContains('Using .psh.xml extended by .psh.xml.override', MockWriter::$content);
        $this->assertContains('override', MockWriter::$content);
        $this->assertContains('override-app', MockWriter::$content);
    }

    public function test_psh_config_override_should_override_existing_psh_configuration_executes_with_override_params()
    {
        $application = new Application(__DIR__ . '/_override_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'override-app', '--external_param', 'foo-content']);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);
        $this->assertContains('Using .psh.xml extended by .psh.xml.override', MockWriter::$content);
        $this->assertContains('override', MockWriter::$content);
        $this->assertContains('override-app', MockWriter::$content);
        $this->assertNotContains('_EXTERNAL_PARAM_', MockWriter::$content);
        $this->assertContains('foo-content', MockWriter::$content);
    }

    public function test_psh_groups_commands_by_environment()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['']);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);

        $this->assertContains('Using .psh.xml', MockWriter::$content);
        $this->assertContains('default:', MockWriter::$content);
        $this->assertContains('test:', MockWriter::$content);
    }

    public function test_bash_autocomplete_listing()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'bash_autocompletion_dump']);

        $this->assertEquals(Application::RESULT_SUCCESS, $exitCode);

        $this->assertNotContains('Using .psh.xml', MockWriter::$content);
        $this->assertContains('error simple test:env test:env2', MockWriter::$content);
    }

    public function test_script_not_found_listing_with_guess()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'sümple']);

        $this->assertEquals(Application::RESULT_ERROR, $exitCode);

        $this->assertContains('Using .psh.xml', MockWriter::$content);
        $this->assertContains('Have you been looking for this?', MockWriter::$content);
        $this->assertContains('- simple', MockWriter::$content);
        $this->assertContains('1 script(s) available', MockWriter::$content);
    }

    public function test_script_not_found_listing_without_guess()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'pkdi']);

        $this->assertEquals(Application::RESULT_ERROR, $exitCode);

        $this->assertContains('Using .psh.xml', MockWriter::$content);
        $this->assertContains('Script with name pkdi not found', MockWriter::$content);
    }

    public function test_showListings_returns_no_scripts_available()
    {
        $application = new Application(__DIR__);
        MockWriter::addToApplication($application);

        $application->showListing([]);

        $this->assertContains('Currently no scripts available', MockWriter::$content);
    }

    public function test_it_throws_exception_InvalidArgumentException_and_it_is_catched()
    {
        $application = new Application(__DIR__ . '/_app_invalid_template');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        $this->assertContains('Unable to find a file referenced by "templates/testa.tpl', MockWriter::$content);
        $this->assertEquals(Application::RESULT_ERROR, $exitCode);
    }

    public function test_it_throws_exception_InvalidParameterException_and_it_is_catched()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple', '-param']);

        $this->assertContains('Unable to parse parameter -param', MockWriter::$content);
        $this->assertEquals(Application::RESULT_ERROR, $exitCode);
    }

    private function assertNoErrorExitCode(int $exitCode)
    {
        $this->assertEquals(0, $exitCode, 'Application errored unexpextedly: ' . MockWriter::$content);
    }

    private function assertSimpleScript(int $exitCode, string $envName)
    {
        $this->assertNoErrorExitCode($exitCode);
        $this->assertContains('ls -al', MockWriter::$content);
        $this->assertContains('Using .psh.xml', MockWriter::$content);
        $this->assertContains('(1/4) Starting', MockWriter::$content);
        $this->assertContains('(2/4) Starting', MockWriter::$content);
        $this->assertContains('(3/4) Starting', MockWriter::$content);
        $this->assertContains('(4/4) Deferring', MockWriter::$content);
        $this->assertContains('WAITING...', MockWriter::$content);
        $this->assertContains('(1/1) Output from', MockWriter::$content);
        $this->assertContains(' echo "' . $envName . '"', MockWriter::$content);
        $this->assertContains('All commands successfully executed!', MockWriter::$content);
        $this->assertContains('Duration:', MockWriter::$content);

        self::assertStringEqualsFile(__DIR__ . '/_app/result.txt', $envName);
    }
}
