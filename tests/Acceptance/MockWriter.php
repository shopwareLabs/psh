<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Acceptance;

use League\CLImate\Util\Writer\WriterInterface;
use League\CLImate\Util\UtilFactory;
use Shopware\Psh\Application\Application;
use Shopware\Psh\Application\ClimateLogger;

class MockWriter implements WriterInterface
{
    public static $content = '';

    /**
     * @param  string $content
     * @return void
     */
    public function write($content)
    {
        self::$content .= $content;
    }

    public static function addToApplication(Application $application)
    {
        self::$content = '';

        $application->cliMate->setUtil(new MockUtilFactory());

        $application->cliMate->output->add('out', new self());
        $application->cliMate->output->add('error', new self());
        $application->cliMate->output->add('buffer', new self());
        $application->cliMate->output->defaultTo('out');
    }

    public static function addToClimateLogger(ClimateLogger $climateLogger)
    {
        self::$content = '';

        $climateLogger->cliMate->output->add('out', new self());
        $climateLogger->cliMate->output->add('error', new self());
        $climateLogger->cliMate->output->add('buffer', new self());
        $climateLogger->cliMate->output->defaultTo('out');
    }
}


class MockUtilFactory extends UtilFactory
{
    public function width()
    {
        return 80;
    }

    /**
     * Get the height of the terminal
     *
     * @return integer
     */

    public function height()
    {
        return 25;
    }
}
