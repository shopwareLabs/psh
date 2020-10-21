<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Acceptance;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Application\Application;
use Shopware\Psh\Application\ExitSignal;
use function file_get_contents;
use function unlink;

class ApplicationTest extends TestCase
{
    /**
     * @before
     * @after
     */
    public function clearCreatedResults(): void
    {
        @unlink(__DIR__ . '/_app/result.txt');
        @unlink(__DIR__ . '/_app_missing_requirement/result.txt');
    }

    public function test_application_listing(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run([]);

        $this->assertNoErrorExitCode($exitCode);
        $this->assertStringContainsString('Using .psh.xml', MockWriter::$content);
        $this->assertStringContainsString('Available commands:', MockWriter::$content);
        $this->assertStringContainsString('test:env', MockWriter::$content);
        $this->assertStringContainsString('test:env2', MockWriter::$content);
        $this->assertStringContainsString('7 script(s) available', MockWriter::$content);
        $this->assertStringNotContainsString('Duration:', MockWriter::$content);
        $this->assertStringNotContainsString('test:.hidden', MockWriter::$content);
    }

    public function test_hidden_execution(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test:.hidden']);

        $this->assertSimpleScript($exitCode, 'test');
    }

    public function test_application_execution(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        $this->assertSimpleScript($exitCode, 'prod');
    }

    public function test_environment_application_execution(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test:env']);

        $this->assertSimpleScript($exitCode, 'test');
    }

    public function test_hidden_environment_application_execution(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'hidden:env']);

        $this->assertSimpleScript($exitCode, 'hidden');
    }

    public function test_environment_deferred_application_execution(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'test:deferred']);

        $this->assertNoErrorExitCode($exitCode);
        $this->assertStringContainsString('Using .psh.xml', MockWriter::$content);
        $this->assertStringContainsString('(1/3) Deferring', MockWriter::$content);
        $this->assertStringContainsString('(2/3) Waiting', MockWriter::$content);
        $this->assertStringContainsString('WAITING...', MockWriter::$content);
        $this->assertStringContainsString('(1/1) Output from', MockWriter::$content);
        $this->assertStringNotContainsString('echo "__ENV__"', MockWriter::$content);
        $this->assertStringContainsString('(3/3) Deferring', MockWriter::$content);
        $this->assertStringContainsString(' echo "test"', MockWriter::$content);
        $this->assertStringContainsString('All commands successfully executed!', MockWriter::$content);
        $this->assertStringContainsString('Duration:', MockWriter::$content);
        $this->assertStringEqualsFile(__DIR__ . '/_app/result.txt', 'test');
    }

    public function test_error_application_execution(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'error']);
        $this->assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
        $this->assertNotEquals(0, $exitCode);
    }

    public function test_chain_two_commands_with_a_comma_executes_both(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'simple,test:env']);

        $this->assertNoErrorExitCode($exitCode);
        $this->assertStringContainsString('Using .psh.xml', MockWriter::$content);
        $this->assertStringContainsString(' echo "prod"', MockWriter::$content);
        $this->assertStringContainsString(' echo "test"', MockWriter::$content);
        $this->assertStringContainsString('All commands successfully executed!', MockWriter::$content);
        $this->assertStringNotContainsString('3 script(s) available', MockWriter::$content);
        $this->assertStringContainsString('Duration:', MockWriter::$content);
        $this->assertStringEqualsFile(__DIR__ . '/_app/result.txt', 'test');
    }

    public function test_chain_two_commands_with_a_comma_executes_both_unless_an_error_occures(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'error,test:env']);

        $this->assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
        $this->assertStringContainsString('Using .psh.xml', MockWriter::$content);
        $this->assertStringNotContainsString(' echo "test"', MockWriter::$content);
        $this->assertStringNotContainsString('All commands successfully executed!', MockWriter::$content);
        $this->assertStringNotContainsString('3 script(s) available', MockWriter::$content);
    }

    public function test_psh_config_override_should_override_existing_psh_configuration(): void
    {
        $application = new Application(__DIR__ . '/_override_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run([]);

        $this->assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);
        $this->assertStringContainsString('Using .psh.xml extended by .psh.xml.override', MockWriter::$content);
        $this->assertStringContainsString('override', MockWriter::$content);
        $this->assertStringContainsString('override-app', MockWriter::$content);
    }

    public function test_psh_config_override_should_override_existing_psh_configuration_executes_with_override_params(): void
    {
        $application = new Application(__DIR__ . '/_override_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'override-app', '--external_param', 'foo-content']);

        $this->assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);
        $this->assertStringContainsString('Using .psh.xml extended by .psh.xml.override', MockWriter::$content);
        $this->assertStringContainsString('Importing foo/.psh.xml from "foo"', MockWriter::$content);

        $this->assertStringContainsString('override', MockWriter::$content);
        $this->assertStringContainsString('override-app', MockWriter::$content);
        $this->assertStringNotContainsString('_EXTERNAL_PARAM_', MockWriter::$content);
        $this->assertStringContainsString('foo-content', MockWriter::$content);
    }

    public function test_psh_groups_commands_by_environment(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['']);

        $this->assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);

        $this->assertStringContainsString('Using .psh.xml', MockWriter::$content);
        $this->assertStringContainsString('default:', MockWriter::$content);
        $this->assertStringContainsString('test:', MockWriter::$content);
    }

    public function test_bash_autocomplete_listing(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'bash_autocompletion_dump']);

        $this->assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);

        $this->assertStringNotContainsString('Using .psh.xml', MockWriter::$content);
        $this->assertStringContainsString('error simple test:env test:env2', MockWriter::$content);
    }

    public function test_script_not_found_listing_with_guess(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'sümple']);

        $this->assertEquals(ExitSignal::RESULT_ERROR, $exitCode);

        $this->assertStringContainsString('Using .psh.xml', MockWriter::$content);
        $this->assertStringContainsString('Have you been looking for this?', MockWriter::$content);
        $this->assertStringContainsString('- simple', MockWriter::$content);
        $this->assertStringContainsString('1 script(s) available', MockWriter::$content);
    }

    public function test_script_not_found_listing_without_guess(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'pkdi']);

        $this->assertEquals(ExitSignal::RESULT_ERROR, $exitCode);

        $this->assertStringContainsString('Using .psh.xml', MockWriter::$content);
        $this->assertStringContainsString('Script with name pkdi not found', MockWriter::$content);
    }

    public function test_showListings_returns_no_scripts_available(): void
    {
        $application = new Application(__DIR__);
        MockWriter::addToApplication($application);

        $application->showListing([]);

        $this->assertStringContainsString('Currently no scripts available', MockWriter::$content);
    }

    public function test_it_throws_exception_InvalidArgumentException_and_it_is_catched(): void
    {
        $application = new Application(__DIR__ . '/_app_invalid_template');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        $this->assertStringContainsString('Unable to find a file referenced by "templates/testa.tpl', MockWriter::$content);
        $this->assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
    }

    public function test_it_exits_early_without_all_requirements_met(): void
    {
        $application = new Application(__DIR__ . '/_app_missing_requirement');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        $this->assertFileNotExists(__DIR__ . '/_app_missing_requirement/result.txt');

        $this->assertStringContainsString('- Missing required const or var named FOO (needs_foo)', MockWriter::$content);
        $this->assertStringContainsString('- Missing required const or var named BAR', MockWriter::$content);
        $this->assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
    }

    public function test_requirements_can_be_overwritten_by_param(): void
    {
        $application = new Application(__DIR__ . '/_app_missing_requirement');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple', '--foo=foho', '--bar=bahar']);

        $this->assertFileExists(__DIR__ . '/_app_missing_requirement/result.txt');
        $this->assertSame('test bahar', file_get_contents(__DIR__ . '/_app_missing_requirement/result.txt'));

        $this->assertStringContainsString('echo "foho"', MockWriter::$content);
        $this->assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);
    }

    public function test_it_throws_exception_InvalidParameterException_and_it_is_catched(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple', '-param']);

        $this->assertStringContainsString('Unable to parse parameter -param', MockWriter::$content);
        $this->assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
    }

    private function assertNoErrorExitCode(int $exitCode): void
    {
        $this->assertEquals(0, $exitCode, 'Application errored unexpextedly: ' . MockWriter::$content);
    }

    private function assertSimpleScript(int $exitCode, string $envName): void
    {
        $this->assertNoErrorExitCode($exitCode);
        $this->assertStringContainsString('ls -al', MockWriter::$content);
        $this->assertStringContainsString('Using .psh.xml', MockWriter::$content);
        $this->assertStringContainsString('Importing glob/.psh.xml extended by glob/.psh.xml.override from "gl*b"', MockWriter::$content);
        $this->assertStringContainsString('NOTICE: No import found for path "foo/"', MockWriter::$content);
        $this->assertStringContainsString('(1/4) Starting', MockWriter::$content);
        $this->assertStringContainsString('(2/4) Starting', MockWriter::$content);
        $this->assertStringContainsString('(3/4) Starting', MockWriter::$content);
        $this->assertStringContainsString('(4/4) Deferring', MockWriter::$content);
        $this->assertStringContainsString('WAITING...', MockWriter::$content);
        $this->assertStringContainsString('(1/1) Output from', MockWriter::$content);
        $this->assertStringContainsString(' echo "' . $envName . '"', MockWriter::$content);
        $this->assertStringContainsString('All commands successfully executed!', MockWriter::$content);
        $this->assertStringContainsString('Duration:', MockWriter::$content);

        $this->assertStringEqualsFile(__DIR__ . '/_app/result.txt', $envName);
    }
}
