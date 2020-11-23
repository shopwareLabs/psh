<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Application;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Psh\Application\ApplicationFactory;
use Shopware\Psh\Application\ParameterParser;
use Shopware\Psh\Config\ConfigLogger;

class ApplicationFactoryTest extends TestCase
{
    public function test_createConfig(): void
    {
        $testParams = [
            './psh',
            'unit',
            '--filter',
            '--filter aaaa',
        ];

        $factory = new ParameterParser();

        $parsedParams = $factory->parseParams($testParams);

        $expectedResult = [
            'filter' => '--filter aaaa',
        ];

        self::assertEquals($expectedResult, $parsedParams);
    }

    public function test_createConfig_with_invalid_config_file(): void
    {
        $testParams = [
            './psh',
            'unit',
        ];

        $factory = $this->getApplicationFactory();

        $this->expectException(RuntimeException::class);

        $factory->createConfig(
            $this->prophesize(ConfigLogger::class)->reveal(),
            __DIR__ . '/_fixtures_with_invalid_config_files/config/.psh.not-supported',
            $testParams
        );
    }

    public function test_reformatParams_expects_exception(): void
    {
        $paramParser = new ParameterParser();

        $this->expectException(RuntimeException::class);
        $paramParser->parseParams(['./psh', 'unit', 'someFalseParameter']);
    }

    public function test_reformatParams_expects_array(): void
    {
        $paramParser = new ParameterParser();
        $testParams = [
            './psh',
            'unit',
            '--env1=dev',
            '--env2',
            'dev',
            '--env3="dev"',
            '--env4="dev"',
            '--env5="gh""ttg"',
            '--env6="gh""t=tg"',
        ];

        $result = $paramParser->parseParams($testParams);

        $expectedResult = [
            'env1' => 'dev',
            'env2' => 'dev',
            'env3' => 'dev',
            'env4' => 'dev',
            'env5' => 'gh""ttg',
            'env6' => 'gh""t=tg',
        ];

        self::assertEquals($expectedResult, $result);
    }

    private function getApplicationFactory(): ApplicationFactory
    {
        return new ApplicationFactory();
    }
}
