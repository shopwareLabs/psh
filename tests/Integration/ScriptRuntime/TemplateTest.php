<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Integration\ScriptRuntime;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Config\Template;
use Shopware\Psh\ScriptRuntime\Execution\TemplateNotValidException;
use function unlink;

class TemplateTest extends TestCase
{
    public function test_get_content_on_invalid_file_fails(): void
    {
        $template = new Template('not-avaliable', '**');
        $this->expectException(TemplateNotValidException::class);
        $template->getContent();
    }

    public function test_it_reads_a_files_content(): void
    {
        $template = new Template(__DIR__ . '/_test_read.tpl', '**');

        self::assertEquals('foo::bar::baz', $template->getContent());
    }

    public function test_it_dumps_the_contents_then(): void
    {
        $template = new Template(__DIR__ . '/_test_write.tpl', __DIR__ . '/_test_write.tpl');

        $template->setContents('test');
        self::assertEquals('test', $template->getContent());
    }

    /**
     * @before
     * @after
     */
    public function removeState(): void
    {
        @unlink(__DIR__ . '/_test_write.tpl');
    }
}
