<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Unit\Integration\ScriptRuntime;

use Shopware\Psh\ScriptRuntime\Template;

class TemplateTest extends \PHPUnit_Framework_TestCase
{
    public function test_get_content_on_invalid_file_fails()
    {
        $template = new Template(uniqid('foo'), '**');

        $exception = null;
        try {
            $template->getContent();
        } catch (\InvalidArgumentException $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function test_it_reads_a_files_content()
    {
        $template = new Template(__DIR__ . '/_test_read.tpl', '**');

        $this->assertEquals('foo::bar::baz', $template->getContent());
    }

    public function test_it_dumps_the_contents_then()
    {
        $template = new Template(__DIR__ . '/_test_write.tpl', __DIR__ . '/_test_write.tpl');

        $template->setContents('test');
        $this->assertEquals('test', $template->getContent());
    }

    /**
     * @before
     * @after
     */
    public function removeState()
    {
        @unlink(__DIR__ . '/_test_write.tpl');
    }
}
