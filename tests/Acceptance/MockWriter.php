<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Acceptance;

use League\CLImate\Util\Writer\WriterInterface;
use Shopware\Psh\Application\Application;
use Shopware\Psh\Application\ClimateLogger;

class MockWriter implements WriterInterface
{
    public static $content = '';

    /**
     * @param  string $content
     */
    public function write($content): void
    {
        self::$content .= $content;
    }

    public static function addToApplication(Application $application): void
    {
        self::$content = '';

        $application->cliMate->output->add('out', new self());
        $application->cliMate->output->add('error', new self());
        $application->cliMate->output->add('buffer', new self());
        $application->cliMate->output->defaultTo('out');
    }

    public static function addToClimateLogger(ClimateLogger $climateLogger): void
    {
        self::$content = '';

        $climateLogger->cliMate->output->add('out', new self());
        $climateLogger->cliMate->output->add('error', new self());
        $climateLogger->cliMate->output->add('buffer', new self());
        $climateLogger->cliMate->output->defaultTo('out');
    }
}
