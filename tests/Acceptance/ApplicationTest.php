<?php declare (strict_types = 1);


namespace Shopware\Acceptance\Test;

use League\CLImate\Util\Writer\WriterInterface;
use Shopware\Psh\Application\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function test_application_listing()
    {
        MockWriter::$content = '';
        $application = new Application(__DIR__ . '/_app');
        $application->cliMate->output->add('out', new MockWriter());
        $application->cliMate->output->add('error', new MockWriter());
        $application->cliMate->output->add('buffer', new MockWriter());
        $application->cliMate->output->defaultTo('out');

        $application->run([]);

        $this->assertNotFalse(strpos(MockWriter::$content, '2 script(s) available'));
    }

    public function test_application_execution()
    {
        MockWriter::$content = '';
        $application = new Application(__DIR__ . '/_app');
        $application->cliMate->output->add('out', new MockWriter());
        $application->cliMate->output->add('error', new MockWriter());
        $application->cliMate->output->add('buffer', new MockWriter());
        $application->cliMate->output->defaultTo('out');

        $application->run(['', 'simple']);

        $this->assertNotFalse(strpos(MockWriter::$content, 'ls -al'));
        $this->assertNotFalse(strpos(MockWriter::$content, '(1/3) Starting'));
        $this->assertNotFalse(strpos(MockWriter::$content, '(2/3) Starting'));
        $this->assertNotFalse(strpos(MockWriter::$content, '(3/3) Starting'));
        $this->assertNotFalse(strpos(MockWriter::$content, ' echo "prod"'));
        $this->assertNotFalse(strpos(MockWriter::$content, 'All commands successfully executed!'));
    }

    public function test_environment_application_execution()
    {
        MockWriter::$content = '';
        $application = new Application(__DIR__ . '/_app');
        $application->cliMate->output->add('out', new MockWriter());
        $application->cliMate->output->add('error', new MockWriter());
        $application->cliMate->output->add('buffer', new MockWriter());
        $application->cliMate->output->defaultTo('out');

        $application->run(['', 'test:env']);

        $this->assertNotFalse(strpos(MockWriter::$content, 'ls -al'), 'ls -al');
        $this->assertNotFalse(strpos(MockWriter::$content, '(1/3) Starting'), '(1/3) Starting');
        $this->assertNotFalse(strpos(MockWriter::$content, '(2/3) Starting'), '(2/3) Starting');
        $this->assertNotFalse(strpos(MockWriter::$content, '(3/3) Starting'), '(3/3) Starting');
        $this->assertNotFalse(strpos(MockWriter::$content, ' echo "test"'), ' echo "test"');
        $this->assertNotFalse(strpos(MockWriter::$content, 'All commands successfully executed!'), 'All commands successfully executed!');
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
