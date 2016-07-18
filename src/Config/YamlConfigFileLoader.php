<?php declare (strict_types = 1);


namespace Shopware\Psh\Config;

use Symfony\Component\Yaml\Parser;

class YamlConfigFileLoader implements ConfigLoader
{
    const KEY_HEADER = 'header';

    const KEY_DYNAMIC_VARIABLES = 'dynamic';

    const KEY_CONST_VARIABLES = 'const';

    const KEY_COMMAND_PATHS = 'paths';
    
    /**
     * @var Parser
     */
    private $yamlReader;

    /**
     * @param Parser $yamlReader
     */
    public function __construct(Parser $yamlReader)
    {
        $this->yamlReader = $yamlReader;
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

        $header = $this->extractData(self::KEY_HEADER, $rawConfigData, false);
        $commandPaths = $this->extractCommandPaths($file, $rawConfigData);
        $environmentVariable = $this->extractData(self::KEY_DYNAMIC_VARIABLES, $rawConfigData);
        $constants = $this->extractData(self::KEY_CONST_VARIABLES, $rawConfigData);

        return new Config(
            $header,
            $commandPaths,
            $environmentVariable,
            $constants
        );
    }

    /**
     * @param string $key
     * @param array $rawConfig
     * @param bool $strict
     * @return mixed|null
     */
    private function extractData(string $key, array $rawConfig, bool $strict = true)
    {
        if (!array_key_exists($key, $rawConfig)) {
            if ($strict) {
                throw new \InvalidArgumentException('Config does not contain "' . $key . '"');
            }

            return null;
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

        if (false === $contents) {
            throw new \RuntimeException('Unable to load config data - read failed in "' . $file . '".');
        }

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
        $paths = $this->extractData(self::KEY_COMMAND_PATHS, $rawConfigData);
        $splitPaths = [];

        foreach ($paths as $path) {
            if (false === strpos($path, ':')) {
                $splitPaths[] = $path;
                continue;
            }

            list($namespace, $namespacePath) = explode(':', $path);
            $splitPaths[$namespace] = $namespacePath;
        }


        return array_map(function ($path) use ($file) {
            if (file_exists($path)) {
                return $path;
            }

            return pathinfo($file, PATHINFO_DIRNAME) . '/' . $path;

        }, $splitPaths);
    }
}
