<?php declare (strict_types = 1);


namespace Shopware\Psh\Test\Unit\Config;

use Shopware\Psh\Config\ConfigFileFinder;

class ConfigFileFinderTest extends \PHPUnit_Framework_TestCase
{
    public function test_config_loader_can_be_created()
    {
        $this->assertInstanceOf(ConfigFileFinder::class, new ConfigFileFinder());
    }
}
