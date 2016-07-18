<?php declare (strict_types = 1);


namespace Shopware\Psh\Test\Integration\Config;

use Shopware\Psh\Config\ConfigFileFinder;

class ConfigFileFinderTest extends \PHPUnit_Framework_TestCase
{
    public function test_config_loader_throw_when_it_cant_find_a_psh_file()
    {
        $loader = new ConfigFileFinder();

        $this->setExpectedException(\RuntimeException::class);
        $loader->discoverFile(sys_get_temp_dir());
    }

    public function test_config_loader_returns_file_if_found()
    {
        $loader = new ConfigFileFinder();

        $file = $loader->discoverFile(__DIR__ . '/_configFileFinderFixtures/sub/sub2/sub3');
        $this->assertEquals(__DIR__ . '/_configFileFinderFixtures/.psh.yaml', $file);
    }

    public function test_config_loader_returns_file_in_same_directory_if_found()
    {
        $loader = new ConfigFileFinder();

        $file = $loader->discoverFile(__DIR__ . '/_configFileFinderFixtures');
        $this->assertEquals(__DIR__ . '/_configFileFinderFixtures/.psh.yaml', $file);
    }
}
