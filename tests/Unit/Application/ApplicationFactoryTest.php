<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Application;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Psh\Application\ApplicationFactory;
use Shopware\Psh\Application\ParameterParser;

class ApplicationFactoryTest extends TestCase
{
    public function test_createConfig()
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
            'FILTER' => '--filter aaaa',
        ];

        $this->assertEquals($expectedResult, $parsedParams);
    }

    public function test_createConfig_with_invalid_config_file()
    {
        $testParams = [
            './psh',
            'unit',
        ];

        $factory = $this->getApplicationFactory();

        $this->expectException(RuntimeException::class);

        $factory->createConfig(
            __DIR__ . '/_fixtures_with_invalid_config_files/config/.psh.not-supported',
            $testParams
        );
    }

    public function test_reformatParams_expects_exception()
    {
        $paramParser = new ParameterParser();

        $this->expectException(RuntimeException::class);
        $paramParser->parseParams(['./psh', 'unit', 'someFalseParameter']);
    }

    public function test_reformatParams_expects_array()
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
            'ENV1' => 'dev',
            'ENV2' => 'dev',
            'ENV3' => 'dev',
            'ENV4' => 'dev',
            'ENV5' => 'gh""ttg',
            'ENV6' => 'gh""t=tg',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    private function getApplicationFactory(): ApplicationFactory
    {
        return new ApplicationFactory();
    }
}
