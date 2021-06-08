<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Application;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Application\ApplicationOptions;
use Shopware\Psh\Application\ParameterParser;
use Shopware\Psh\Config\ConfigLogger;

class ParameterParserTest extends TestCase
{

    public function provideData()
    {
        return [
            [[], [], [], []],
            [['./psh'], [], [], []],
            [['./psh', 'unit'], [], ['unit'], []],
            [['./psh', 'init,unit'], [], ['init', 'unit'], []],
            [['./psh', 'init,unit', '--command=unit'], [], ['init', 'unit'], ['command' => 'unit']],
            [['./psh', '--no-header'], [ApplicationOptions::FLAG_NO_HEADER], [], []],
            [['./psh', '--no-header', 'init'], [ApplicationOptions::FLAG_NO_HEADER], ['init'], []],
            [['./psh', '--no-header', 'init,unit'], [ApplicationOptions::FLAG_NO_HEADER], ['init', 'unit'], []],
            [['./psh', '--no-header', '--no-header', '--no-header', '--no-header', 'init,unit'], [ApplicationOptions::FLAG_NO_HEADER], ['init', 'unit'], []],
            [['./psh', '--no-header', '--no-header', '--no-header', '--no-header', 'init,unit', '--command', 'unit'], [ApplicationOptions::FLAG_NO_HEADER], ['init', 'unit'], ['command' => 'unit']],
            [
                ['./psh', 'unit', '--env1=dev', '--env2', 'dev', '--env3="dev"', '--env4="dev"', '--env5="gh""ttg"', '--env6="gh""t=tg"'],
                [],
                ['unit'],
                ['env1' => 'dev', 'env2' => 'dev', 'env3' => 'dev', 'env4' => 'dev', 'env5' => 'gh""ttg', 'env6' => 'gh""t=tg']
            ],
            [
                ['./psh', 'unit', '--filter', '--filter aaaa'],
                [],
                ['unit'],
                ['filter' => '--filter aaaa']
            ]
        ];
    }

    public function test_app_options(): void
    {
        self::assertSame(['FLAG_NO_HEADER' => '--no-header'], ApplicationOptions::getAllFlags());
    }

    /**
     * @dataProvider provideData
     */
    public function test_possibilities(array $in, array $appParams, array $commands, array $overwrites)
    {
        $result = (new ParameterParser())->parseAllParams($in);

        self::assertSame($appParams, $result->getAppParams(), 'App param broken');
        self::assertSame($commands, $result->getCommands(), 'commands broken');
        self::assertSame($overwrites, $result->getOverwrites(), 'overwrites broken');
    }

    public function test_reformatParams_expects_exception(): void
    {
        $paramParser = new ParameterParser();

        $this->expectException(\RuntimeException::class);
        $paramParser->parseAllParams(['./psh', 'unit', 'someFalseParameter']);
    }
}
