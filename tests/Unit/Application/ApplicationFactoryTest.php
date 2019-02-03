<?php

namespace Shopware\Psh\Test\Unit\Application;

use Shopware\Psh\Application\ApplicationFactory;
use Shopware\Psh\Config\Config;

class ApplicationFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function test_createConfig()
    {
        $testParams = [
            './psh',
            'unit',
            '--filter',
            '--filter aaaa',
        ];

        $factory = $this->getApplicationFactory();

        $result = $factory->createConfig(__DIR__ . '/_fixtures/config/.psh.yaml', $testParams);

        $property = $this->getPrivateProperty(Config::class, 'params');
        $result2 = $property->getValue($result);

        $expectedResult = [
            'FILTER' => '--filter aaaa',
        ];

        $this->assertInstanceOf(Config::class, $result);
        $this->assertEquals($expectedResult, $result2);
    }

    public function test_createConfig_with_invalid_config_file()
    {
        $testParams = [
            './psh',
            'unit'
        ];

        $factory = $this->getApplicationFactory();

        $this->expectException(\RuntimeException::class);

        $factory->createConfig(
            __DIR__ . '/_fixtures_with_invalid_config_files/config/.psh.not-supported',
            $testParams
        );
    }

    public function test_reformatParams_expects_exception()
    {
        $factory = $this->getApplicationFactory();
        $method = $this->getPrivateMethod(ApplicationFactory::class, 'reformatParams');

        $this->expectException(\RuntimeException::class);
        $method->invokeArgs($factory, [['./psh', 'unit', 'someFalseParameter']]);
    }

    public function test_reformatParams_expects_array()
    {
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

        $factory = $this->getApplicationFactory();
        $method = $this->getPrivateMethod(ApplicationFactory::class, 'reformatParams');

        $result = $method->invokeArgs($factory, [$testParams]);

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

    /**
     * @param string $class
     * @param string $method
     * @return \ReflectionMethod
     */
    private function getPrivateMethod(string $class, string $method): \ReflectionMethod
    {
        $reflectionClass = new \ReflectionClass($class);
        $method = $reflectionClass->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @param string $class
     * @param string $property
     * @return \ReflectionProperty
     */
    private function getPrivateProperty(string $class, string $property): \ReflectionProperty
    {
        $reflectionClass = new \ReflectionClass($class);
        $property = $reflectionClass->getProperty($property);
        $property->setAccessible(true);

        return $property;
    }

    /**
     * @return ApplicationFactory
     */
    private function getApplicationFactory(): ApplicationFactory
    {
        return new ApplicationFactory();
    }
}
