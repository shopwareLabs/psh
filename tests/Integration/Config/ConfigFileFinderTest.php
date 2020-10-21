<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Integration\Config;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Psh\Config\ConfigFileFinder;
use function sys_get_temp_dir;

class ConfigFileFinderTest extends TestCase
{
    public function test_config_loader_throw_when_it_cant_find_a_psh_file(): void
    {
        $loader = new ConfigFileFinder();

        $this->expectException(RuntimeException::class);
        $loader->discoverFiles(sys_get_temp_dir());
    }

    public function test_config_loader_returns_file_if_found(): void
    {
        $loader = new ConfigFileFinder();

        $file = $loader->discoverFiles(__DIR__ . '/_configFileFinderFixtures/dist/sub/sub2/sub3');
        self::assertEquals([__DIR__ . '/_configFileFinderFixtures/dist/.psh.xml'], $file);
    }

    public function test_config_loader_returns_file_in_same_directory_if_found(): void
    {
        $loader = new ConfigFileFinder();

        $file = $loader->discoverFiles(__DIR__ . '/_configFileFinderFixtures/dist');
        self::assertEquals([__DIR__ . '/_configFileFinderFixtures/dist/.psh.xml'], $file);
    }

    public function test_config_loader_prefers_original_over_dist_file(): void
    {
        $loader = new ConfigFileFinder();

        $file = $loader->discoverFiles(__DIR__ . '/_configFileFinderFixtures/dist');
        self::assertEquals([__DIR__ . '/_configFileFinderFixtures/dist/.psh.xml'], $file);
    }

    public function test_config_loader_returns_override_file(): void
    {
        $loader = new ConfigFileFinder();

        $files = $loader->discoverFiles(__DIR__ . '/_configFileFinderFixtures/override');

        self::assertEquals([
            __DIR__ . '/_configFileFinderFixtures/override/.psh.xml',
            __DIR__ . '/_configFileFinderFixtures/override/.psh.xml.override',
        ], $files);
    }

    public function test_config_loader_returns_dist_and_override_file(): void
    {
        $loader = new ConfigFileFinder();

        $files = $loader->discoverFiles(__DIR__ . '/_configFileFinderFixtures/override_and_dist');

        self::assertEquals([
            __DIR__ . '/_configFileFinderFixtures/override_and_dist/.psh.xml.dist',
            __DIR__ . '/_configFileFinderFixtures/override_and_dist/.psh.xml.override',
        ], $files);
    }

    public function test_config_loader_returns_dist_xml_and_override_yaml_file(): void
    {
        $loader = new ConfigFileFinder();

        $files = $loader->discoverFiles(__DIR__ . '/_configFileFinderFixtures/override_yml_and_dist_xml');

        self::assertEquals([
            __DIR__ . '/_configFileFinderFixtures/override_yml_and_dist_xml/.psh.xml.dist',
            __DIR__ . '/_configFileFinderFixtures/override_yml_and_dist_xml/.psh.yaml.override',
        ], $files);
    }

    public function test_discover_config_in_directory_does_not_recurse(): void
    {
        $loader = new ConfigFileFinder();

        $file = $loader->discoverConfigInDirectory(__DIR__ . '/_configFileFinderFixtures/dist/sub/sub2/sub3');
        self::assertEquals([], $file);

        $file = $loader->discoverConfigInDirectory(__DIR__ . '/_configFileFinderFixtures/dist');
        self::assertEquals([__DIR__ . '/_configFileFinderFixtures/dist/.psh.xml'], $file);
    }
}
