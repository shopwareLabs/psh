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
        self::assertStringContainsString(PHP_EOL . 'SHOPWARE PHP-SH' . PHP_EOL, MockWriter::$content);
        self::assertStringContainsString('Using .psh.xml', MockWriter::$content);
        self::assertStringContainsString('Available commands:', MockWriter::$content);
        self::assertStringContainsString('- very-long-script-name-to-test-padding-works-as-expected               desc', MockWriter::$content);
        self::assertStringContainsString('test:env', MockWriter::$content);
        self::assertStringContainsString('test:env2', MockWriter::$content);
        self::assertStringContainsString('8 script(s) available', MockWriter::$content);
        self::assertStringNotContainsString('Duration:', MockWriter::$content);
        self::assertStringNotContainsString('test:.hidden', MockWriter::$content);
    }

    public function test_no_header_option()
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['-', '--no-header']);

        $this->assertNoErrorExitCode($exitCode);
        self::assertStringNotContainsString(PHP_EOL . 'SHOPWARE PHP-SH' . PHP_EOL, MockWriter::$content);
        self::assertStringContainsString('8 script(s) available', MockWriter::$content);

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
        self::assertStringContainsString('Using .psh.xml', MockWriter::$content);
        self::assertStringContainsString('(1/3) Deferring', MockWriter::$content);
        self::assertStringContainsString('(2/3) Waiting', MockWriter::$content);
        self::assertStringContainsString('WAITING...', MockWriter::$content);
        self::assertStringContainsString('(1/1) Output from', MockWriter::$content);
        self::assertStringNotContainsString('echo "__ENV__"', MockWriter::$content);
        self::assertStringContainsString('(3/3) Deferring', MockWriter::$content);
        self::assertStringContainsString(' echo "test"', MockWriter::$content);
        self::assertStringContainsString('All commands successfully executed!', MockWriter::$content);
        self::assertStringContainsString('Duration:', MockWriter::$content);
        self::assertStringEqualsFile(__DIR__ . '/_app/result.txt', 'test');
    }

    public function test_error_application_execution(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'error']);
        self::assertStringEndsWith('Execution aborted, a subcommand failed!' . PHP_EOL . PHP_EOL, MockWriter::$content);
        self::assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
        self::assertNotEquals(0, $exitCode);
    }

    public function test_chain_two_commands_with_a_comma_executes_both(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'simple,test:env']);

        $this->assertNoErrorExitCode($exitCode);
        self::assertStringContainsString('Using .psh.xml', MockWriter::$content);
        self::assertStringContainsString(' echo "prod"', MockWriter::$content);
        self::assertStringContainsString(' echo "test"', MockWriter::$content);
        self::assertStringContainsString('All commands successfully executed!', MockWriter::$content);
        self::assertStringNotContainsString('3 script(s) available', MockWriter::$content);
        self::assertStringContainsString('Duration:', MockWriter::$content);
        self::assertStringEqualsFile(__DIR__ . '/_app/result.txt', 'test');
    }

    public function test_chain_two_commands_with_a_comma_executes_both_unless_an_error_occures(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'error,test:env']);

        self::assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
        self::assertStringContainsString('Using .psh.xml', MockWriter::$content);
        self::assertStringNotContainsString(' echo "test"', MockWriter::$content);
        self::assertStringNotContainsString('All commands successfully executed!', MockWriter::$content);
        self::assertStringNotContainsString('3 script(s) available', MockWriter::$content);
    }

    public function test_psh_config_override_should_override_existing_psh_configuration(): void
    {
        $application = new Application(__DIR__ . '/_override_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run([]);

        self::assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);
        self::assertStringContainsString('Using .psh.xml extended by .psh.xml.override', MockWriter::$content);
        self::assertStringContainsString('override', MockWriter::$content);
        self::assertStringContainsString('override-app', MockWriter::$content);
    }

    public function test_psh_config_override_should_override_existing_psh_configuration_executes_with_override_params(): void
    {
        $application = new Application(__DIR__ . '/_override_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'override-app', '--external_param', 'foo-content']);

        self::assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);
        self::assertStringContainsString('Using .psh.xml extended by .psh.xml.override', MockWriter::$content);
        self::assertStringContainsString('Importing foo/.psh.xml from "foo"', MockWriter::$content);

        self::assertStringContainsString('override', MockWriter::$content);
        self::assertStringContainsString('override-app', MockWriter::$content);
        self::assertStringNotContainsString('_EXTERNAL_PARAM_', MockWriter::$content);
        self::assertStringContainsString('foo-content', MockWriter::$content);
    }

    public function test_psh_groups_commands_by_environment(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['']);

        self::assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);

        self::assertStringContainsString('Using .psh.xml', MockWriter::$content);
        self::assertStringContainsString('default:' . PHP_EOL . " - error", MockWriter::$content);
        self::assertStringContainsString('test:' . PHP_EOL . " - test:env", MockWriter::$content);
    }

    public function test_bash_autocomplete_listing(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'bash_autocompletion_dump']);

        self::assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);

        self::assertStringNotContainsString('Using .psh.xml', MockWriter::$content);
        self::assertStringContainsString('error simple very-long-script-name-to-test-padding-works-as-expected test:env test:env2', MockWriter::$content);
    }

    public function test_script_not_found_listing_with_guess(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'sümple']);

        self::assertEquals(ExitSignal::RESULT_ERROR, $exitCode);

        self::assertStringContainsString('Using .psh.xml', MockWriter::$content);
        self::assertStringContainsString('Have you been looking for this?', MockWriter::$content);
        self::assertStringContainsString('- simple', MockWriter::$content);
        self::assertStringContainsString('1 script(s) available', MockWriter::$content);
    }

    public function test_script_not_found_listing_without_guess(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);
        $exitCode = $application->run(['', 'pkdi']);

        self::assertEquals(ExitSignal::RESULT_ERROR, $exitCode);

        self::assertStringContainsString('Using .psh.xml', MockWriter::$content);
        self::assertStringContainsString('Script with name pkdi not found', MockWriter::$content);
    }

    public function test_showListings_returns_no_scripts_available(): void
    {
        $application = new Application(__DIR__ . '/_app-empty');
        MockWriter::addToApplication($application);

        $application->run([]);

        self::assertStringContainsString('Currently no scripts available', MockWriter::$content);
    }

    public function test_it_throws_exception_InvalidArgumentException_and_it_is_catched(): void
    {
        $application = new Application(__DIR__ . '/_app_invalid_template');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        self::assertStringContainsString('Unable to find a file referenced by "templates/testa.tpl', MockWriter::$content);
        self::assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
    }

    public function test_it_exits_early_without_all_requirements_met(): void
    {
        $application = new Application(__DIR__ . '/_app_missing_requirement');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple']);

        self::assertFileNotExists(__DIR__ . '/_app_missing_requirement/result.txt');

        self::assertStringContainsString('- Missing required const or var named FOO (needs_foo)', MockWriter::$content);
        self::assertStringContainsString('- Missing required const or var named BAR', MockWriter::$content);
        self::assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
    }

    public function test_requirements_can_be_overwritten_by_param(): void
    {
        $application = new Application(__DIR__ . '/_app_missing_requirement');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple', '--foo=foho', '--bar=bahar']);

        self::assertFileExists(__DIR__ . '/_app_missing_requirement/result.txt');
        self::assertSame('test bahar', file_get_contents(__DIR__ . '/_app_missing_requirement/result.txt'));

        self::assertStringContainsString('echo "foho"', MockWriter::$content);
        self::assertEquals(ExitSignal::RESULT_SUCCESS, $exitCode);
    }

    public function test_it_throws_exception_InvalidReferencedPath_and_it_is_catched(): void
    {
        $application = new Application(__DIR__ . '/_app');
        MockWriter::addToApplication($application);

        $exitCode = $application->run(['', 'simple', '-param']);

        self::assertSame(PHP_EOL . 'Unable to parse parameter "-param". Use -- for correct usage' . PHP_EOL. PHP_EOL, MockWriter::$content);
        self::assertEquals(ExitSignal::RESULT_ERROR, $exitCode);
    }

    private function assertNoErrorExitCode(int $exitCode): void
    {
        self::assertEquals(0, $exitCode, 'Application errored unexpextedly: ' . MockWriter::$content);
    }

    private function assertSimpleScript(int $exitCode, string $envName): void
    {
        $this->assertNoErrorExitCode($exitCode);
        self::assertStringContainsString('ls -al', MockWriter::$content);
        self::assertStringContainsString('Using .psh.xml', MockWriter::$content);
        self::assertStringContainsString('Importing glob/.psh.xml extended by glob/.psh.xml.override from "gl*b"', MockWriter::$content);
        self::assertStringContainsString('NOTICE: No import found for path "foo/"', MockWriter::$content);
        self::assertStringContainsString('(1/4) Starting', MockWriter::$content);
        self::assertStringContainsString('(2/4) Starting', MockWriter::$content);
        self::assertStringContainsString('(3/4) Starting', MockWriter::$content);
        self::assertStringContainsString('(4/4) Deferring', MockWriter::$content);
        self::assertStringContainsString('WAITING...', MockWriter::$content);
        self::assertStringContainsString('(1/1) Output from', MockWriter::$content);
        self::assertStringContainsString(' echo "' . $envName . '"', MockWriter::$content);
        self::assertStringContainsString('All commands successfully executed!', MockWriter::$content);
        self::assertStringContainsString('Duration:', MockWriter::$content);

        self::assertStringEqualsFile(__DIR__ . '/_app/result.txt', $envName);
    }
}
