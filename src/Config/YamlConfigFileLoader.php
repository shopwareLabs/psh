<?php declare(strict_types=1);


namespace Shopware\Psh\Config;

use Symfony\Component\Yaml\Parser;

/**
 * Load the config data from a yaml file
 */
class YamlConfigFileLoader implements ConfigLoader
{
    const KEY_HEADER = 'header';

    const KEY_DYNAMIC_VARIABLES = 'dynamic';

    const KEY_CONST_VARIABLES = 'const';

    const KEY_COMMAND_PATHS = 'paths';

    const KEY_ENVIRONMENTS = 'environments';

    const KEY_TEMPLATES = 'templates';

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
        $file = $this->removeDistExtension($file);
        return pathinfo($file, PATHINFO_EXTENSION) === 'yaml' || pathinfo($file, PATHINFO_EXTENSION) === 'yml';
    }

    /**
     * @param string $file
     * @return string
     */
    private function removeDistExtension(string $file): string
    {
        $fileInfo = pathinfo($file);
        if ($fileInfo['extension'] === 'dist') {
            $file = $fileInfo['filename'];
        }

        return $file;
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

        $this->configBuilder->setTemplates(
            array_map(function ($template) use ($file) {
                $template['source'] = $this->fixPath($template['source'], $file);
                $template['destination'] = $this->fixPath($template['destination'], $file);

                return $template;
            }, $this->extractData(self::KEY_TEMPLATES, $rawConfigData, []))
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
        return file_get_contents($file);
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
            return $this->fixPath($path, $file);
        }, $paths);
    }

    /**
     * @param string $absoluteOrRelativePath
     * @param string $baseFile
     * @return string
     */
    private function fixPath(string $absoluteOrRelativePath, string $baseFile): string
    {
        if (file_exists($absoluteOrRelativePath)) {
            return $absoluteOrRelativePath;
        }

        return pathinfo($baseFile, PATHINFO_DIRNAME) . '/' . $absoluteOrRelativePath;
    }
}
