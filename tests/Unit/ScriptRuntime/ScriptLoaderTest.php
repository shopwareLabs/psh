<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\ScriptRuntime;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Listing\Script;
use Shopware\Psh\ScriptRuntime\ScriptLoader\ScriptLoader;
use Shopware\Psh\ScriptRuntime\ScriptLoader\ScriptNotSupportedByParser;

class ScriptLoaderTest extends TestCase
{
    public function test_no_parser_equals_no_support()
    {
        $loader = new ScriptLoader();

        $this->expectException(ScriptNotSupportedByParser::class);
        $loader->loadScript(new Script(__DIR__ . '/_scripts', 'empty.txt', false, ''));
    }
}
