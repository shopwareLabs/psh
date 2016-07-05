<?php declare(strict_types = 1);


namespace Shopware\Acceptance\Test;


use League\CLImate\Util\Writer\WriterInterface;
use Shopware\Psh\Application\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function test_application_listing()
    {
        $application = new Application(__DIR__ . '/_app');
        $application->cliMate->output->add('out', new MockWriter());
        $application->cliMate->output->add('error', new MockWriter());
        $application->cliMate->output->add('buffer', new MockWriter());
        $application->cliMate->output->defaultTo('out');

        $application->run([]);


        $this->assertNotFalse(strpos(MockWriter::$content, '1 script(s) available'));
    }

    public function test_application_execution()
    {
        $application = new Application(__DIR__ . '/_app');
        $application->cliMate->output->add('out', new MockWriter());
        $application->cliMate->output->add('error', new MockWriter());
        $application->cliMate->output->add('buffer', new MockWriter());
        $application->cliMate->output->defaultTo('out');

        $application->run(['', 'simple']);

        $this->assertNotFalse(strpos(MockWriter::$content, 'ls -al'));
    }

}

class MockWriter implements WriterInterface
{
    public static $content = '';

    /**
     * @param  string $content
     *
     * @return void
     */
    public function write($content)
    {
        self::$content .= $content;
    }
}