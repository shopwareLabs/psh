<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Application;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Psh\Application\ApplicationFactory;
use Shopware\Psh\Application\ParameterParser;
use Shopware\Psh\Config\ConfigLogger;

class ApplicationFactoryTest extends TestCase
{
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


    private function getApplicationFactory(): ApplicationFactory
    {
        return new ApplicationFactory();
    }
}
