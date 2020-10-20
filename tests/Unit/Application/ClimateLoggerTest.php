<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Application;

use Khill\Duration\Duration;
use League\CLImate\CLImate;
use PHPUnit\Framework\TestCase;
use Shopware\Psh\Application\ClimateLogger;
use Shopware\Psh\Test\Acceptance\MockWriter;

class ClimateLoggerTest extends TestCase
{
    public function test_success_output(): void
    {
        $cliMateLogger = new ClimateLogger(new CLImate(), new Duration());
        MockWriter::addToClimateLogger($cliMateLogger);
        $cliMateLogger->logSuccess();

        $this->assertStringContainsString('Executed Successfully', MockWriter::$content);
    }

    public function test_error_output(): void
    {
        $cliMateLogger = new ClimateLogger(new CLImate(), new Duration());
        MockWriter::addToClimateLogger($cliMateLogger);
        $cliMateLogger->logFailure();

        $this->assertStringContainsString('Executed with failure', MockWriter::$content);
    }

    public function test_warn_output(): void
    {
        $cliMateLogger = new ClimateLogger(new CLImate(), new Duration());
        MockWriter::addToClimateLogger($cliMateLogger);
        $cliMateLogger->warn('FOOOOOOOOOOOOOOOOO');

        $this->assertStringContainsString('FOOOOOOOOOOOOOOOOO', MockWriter::$content);
    }
}
