<?php

namespace Shopware\Psh\Test\Unit\Application;

use Khill\Duration\Duration;
use League\CLImate\CLImate;
use Shopware\Psh\Application\ClimateLogger;
use Shopware\Psh\Test\Acceptance\MockWriter;

class ClimateLoggerTest extends \PHPUnit\Framework\TestCase
{
    public function test_logTemplate()
    {
        $cliMateLogger = new ClimateLogger(new CLImate(), new Duration());
        MockWriter::addToClimateLogger($cliMateLogger);
        $cliMateLogger->logTemplate('some_destination_string', 11, 22, 0);

        self::assertContains('some_destination_string', MockWriter::$content);
    }
}
