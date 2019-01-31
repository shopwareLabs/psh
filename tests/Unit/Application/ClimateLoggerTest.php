<?php

namespace Shopware\Psh\Test\Unit\Application;

use Khill\Duration\Duration;
use League\CLImate\CLImate;
use Shopware\Psh\Application\ClimateLogger;
use Shopware\Psh\Test\Acceptance\MockWriter;

class ClimateLoggerTest extends \PHPUnit_Framework_TestCase
{
    public function test_success_output()
    {
        $cliMateLogger = new ClimateLogger(new CLImate(), new Duration());
        MockWriter::addToClimateLogger($cliMateLogger);
        $cliMateLogger->logSuccess();

        self::assertSame("Executed Successfully\n", MockWriter::$content);
    }

    public function test_error_output()
    {
        $cliMateLogger = new ClimateLogger(new CLImate(), new Duration());
        MockWriter::addToClimateLogger($cliMateLogger);
        $cliMateLogger->logFailure();

        self::assertSame("Executed with failure\n", MockWriter::$content);
    }
}
