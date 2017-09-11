<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Unit\Config;

use Shopware\Psh\Config\ConfigFileFinder;

class ConfigFileFinderTest extends \PHPUnit\Framework\TestCase
{
    public function test_config_loader_can_be_created()
    {
        $this->assertInstanceOf(ConfigFileFinder::class, new ConfigFileFinder());
    }

    public function test_file_discovery_default_case()
    {
        $finder = new ConfigFileFinder();
        $result = $finder->determineResultInDirectory([
            __DIR__ . '/.psh.yml',
        ]);

        self::assertEquals([__DIR__ . '/.psh.yml'], $result);
    }

    public function test_file_discovery_with_dist_file_only()
    {
        $finder = new ConfigFileFinder();
        $result = $finder->determineResultInDirectory([
            __DIR__ . '/.psh.yml.dist',
        ]);

        self::assertEquals([__DIR__ . '/.psh.yml.dist'], $result);
    }

    public function test_file_discovery_with_dist_file_and_default_file()
    {
        $finder = new ConfigFileFinder();
        $result = $finder->determineResultInDirectory([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.dist',
        ]);

        self::assertEquals([__DIR__ . '/.psh.yml'], $result);
    }

    public function test_file_discovery_with_dist_file_and_default_file_and_override_file()
    {
        $finder = new ConfigFileFinder();
        $result = $finder->determineResultInDirectory([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.dist',
            __DIR__ . '/.psh.yml.override',
        ]);

        self::assertEquals([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.override',
        ], $result);
    }

    public function test_file_discovery_with_default_file_and_override_file()
    {
        $finder = new ConfigFileFinder();
        $result = $finder->determineResultInDirectory([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.override',
        ]);

        self::assertEquals([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.override',
        ], $result);
    }

    public function test_file_discovery_with_dist_file__and_override_file()
    {
        $finder = new ConfigFileFinder();
        $result = $finder->determineResultInDirectory([
            __DIR__ . '/.psh.yml.dist',
            __DIR__ . '/.psh.yml.override',
        ]);

        self::assertEquals([
            __DIR__ . '/.psh.yml.dist',
            __DIR__ . '/.psh.yml.override',
        ], $result);
    }
}
