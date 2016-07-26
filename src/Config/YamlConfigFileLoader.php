<?php declare (strict_types = 1);


namespace Shopware\Psh\Config;

use Symfony\Component\Yaml\Parser;

class YamlConfigFileLoader implements ConfigLoader
{
    const KEY_HEADER = 'header';

    const KEY_DYNAMIC_VARIABLES = 'dynamic';

    const KEY_CONST_VARIABLES = 'const';

    const KEY_COMMAND_PATHS = 'paths';

    const KEY_ENVIRONMENTS = 'environments';

    /**
     * @var Parser
     */
    private $yamlReader;

    /**
     * @var ConfigBuilder
     */
    private $configBuilder;

    /**
     * @param Parser $yamlReader
     * @param ConfigBuilder $configBuilder
     */
    public function __construct(Parser $yamlReader, ConfigBuilder $configBuilder)
    {
        $this->yamlReader = $yamlReader;
        $this->configBuilder = $configBuilder;
    }


    /**
     * @inheritdoc
     */
    public function isSupported(string $file): bool
    {
        return pathinfo($file, PATHINFO_EXTENSION) === 'yaml' || pathinfo($file, PATHINFO_EXTENSION) === 'yml';
    }

    /**
     * @inheritdoc
     */
    public function load(string $file): Config
    {
        $contents = $this->loadFileContents($file);
        $rawConfigData = $this->parseFileContents($contents);

        $this->configBuilder->start();

        $this->configBuilder
            ->setHeader(
            $this->extractData(self::KEY_HEADER, $rawConfigData, '')
        );

        $this->setConfigData($file, $rawConfigData);

        $environments = $this->extractData(self::KEY_ENVIRONMENTS, $rawConfigData, []);
        foreach ($environments as $name => $data) {
            $this->configBuilder->start($name);
            $this->setConfigData($file, $data);
        }

        return $this->configBuilder
            ->create();
    }

    private function setConfigData(string $file, array $rawConfigData)
    {
        $this->configBuilder->setCommandPaths(
            $this->extractCommandPaths($file, $rawConfigData)
        );

        $this->configBuilder->setDynamicVariables(
            $this->extractData(self::KEY_DYNAMIC_VARIABLES, $rawConfigData, [])
        );

        $this->configBuilder->setConstants(
            $this->extractData(self::KEY_CONST_VARIABLES, $rawConfigData, [])
        );
    }

    /**
     * @param string $key
     * @param array $rawConfig
     * @param bool $default
     * @return string|null
     */
    private function extractData(string $key, array $rawConfig, $default = false)
    {
        if (!array_key_exists($key, $rawConfig)) {
            return $default;
        }

        return $rawConfig[$key];
    }

    /**
     * @param string $file
     * @return string
     */
    private function loadFileContents(string $file): string
    {
        $contents = file_get_contents($file);

        return $contents;
    }

    /**
     * @param string $contents
     * @return array
     */
    private function parseFileContents(string $contents): array
    {
        return $this->yamlReader->parse($contents);
    }

    /**
     * @param string $file
     * @param $rawConfigData
     * @return array
     */
    private function extractCommandPaths(string $file, array $rawConfigData): array
    {
        $paths = $this->extractData(self::KEY_COMMAND_PATHS, $rawConfigData, []);

        return array_map(function ($path) use ($file) {
            if (file_exists($path)) {
                return $path;
            }

            return pathinfo($file, PATHINFO_DIRNAME) . '/' . $path;

        }, $paths);
    }
}
