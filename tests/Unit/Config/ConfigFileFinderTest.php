<?php declare(strict_types=1);


namespace Shopware\Psh\Test\Unit\Config;

use Shopware\Psh\ConfigLoad\ConfigFileDiscovery;
use Shopware\Psh\ConfigLoad\ConfigFileFinder;

class ConfigFileFinderTest extends \PHPUnit_Framework_TestCase
{
    private $createdFiles = [];

    private function createFiles(array $files)
    {
        foreach ($files as $file) {
            $this->createdFiles[] = $file;
            touch($file);
        }
    }

    protected function tearDown()
    {
        foreach ($this->createdFiles as $file) {
            unlink($file);
        }
    }

    private function assertResult(array $expectation, ConfigFileDiscovery $result)
    {
        $arrayResult = [$result->getPrimaryFile()];

        if ($result->getOverrideFile()) {
            $arrayResult[] = $result->getOverrideFile();
        }

        self::assertEquals($expectation, $arrayResult);
    }

    public function test_config_loader_can_be_created()
    {
        $this->assertInstanceOf(ConfigFileFinder::class, new ConfigFileFinder());
    }

    public function test_file_discovery_default_case()
    {
        $finder = new ConfigFileFinder();

        $this->createFiles([
            __DIR__ . '/.psh.yml',
        ]);

        $result = $finder->discoverFiles(__DIR__);

        $this->assertResult([__DIR__ . '/.psh.yml'], $result);
    }

    public function test_file_discovery_with_dist_file_only()
    {
        $finder = new ConfigFileFinder();
        $this->createFiles([
            __DIR__ . '/.psh.yml.dist',
        ]);

        $result = $finder->discoverFiles(__DIR__);

        $this->assertResult([__DIR__ . '/.psh.yml.dist'], $result);
    }

    public function test_file_discovery_with_dist_file_and_default_file()
    {
        $finder = new ConfigFileFinder();
        $this->createFiles([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.dist',
        ]);

        $result = $finder->discoverFiles(__DIR__);

        $this->assertResult([__DIR__ . '/.psh.yml'], $result);
    }

    public function test_file_discovery_with_dist_file_and_default_file_and_override_file()
    {
        $finder = new ConfigFileFinder();
        $this->createFiles([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.dist',
            __DIR__ . '/.psh.yml.override',
        ]);

        $result = $finder->discoverFiles(__DIR__);

        $this->assertResult([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.override',
        ], $result);
    }

    public function test_file_discovery_with_default_file_and_override_file()
    {
        $finder = new ConfigFileFinder();
        $this->createFiles([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.override',
        ]);

        $result = $finder->discoverFiles(__DIR__);

        $this->assertResult([
            __DIR__ . '/.psh.yml',
            __DIR__ . '/.psh.yml.override',
        ], $result);
    }

    public function test_file_discovery_with_dist_file__and_override_file()
    {
        $finder = new ConfigFileFinder();
        $this->createFiles([
            __DIR__ . '/.psh.yml.dist',
            __DIR__ . '/.psh.yml.override',
        ]);

        $result = $finder->discoverFiles(__DIR__);

        $this->assertResult([
            __DIR__ . '/.psh.yml.dist',
            __DIR__ . '/.psh.yml.override',
        ], $result);
    }
}
