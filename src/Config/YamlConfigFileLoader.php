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
     * @var string
     */
    private $applicationRootDirectory;

    /**
     * @param Parser $yamlReader
     * @param ConfigBuilder $configBuilder
     * @param string $applicationRootDirectory
     */
    public function __construct(Parser $yamlReader, ConfigBuilder $configBuilder, string $applicationRootDirectory)
    {
        $this->yamlReader = $yamlReader;
        $this->configBuilder = $configBuilder;
        $this->applicationRootDirectory = $applicationRootDirectory;
    }

    /**
     * @inheritdoc
     */
    public function isSupported(string $file): bool
    {
        return in_array(pathinfo($file, PATHINFO_EXTENSION), ['yaml', 'yml', 'dist', 'override'], true);
    }

    /**
     * @inheritdoc
     */
    public function load(string $file, array $params): Config
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
            ->create($params);
    }

    /**
     * @param string $file
     * @param array $rawConfigData
     */
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
            $this->extractTemplates($file, $rawConfigData)
        );
    }

    /**
     * @param string $key
     * @param array $rawConfig
     * @param bool $default
     * @return mixed|null
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
     * @param string $file
     * @param array $rawConfigData
     * @return array
     */
    private function extractTemplates(string $file, array $rawConfigData): array
    {
        $templates = $this->extractData(self::KEY_TEMPLATES, $rawConfigData, []);

        return array_map(function ($template) use ($file) {
            $template['source'] = $this->fixPath($template['source'], $file);
            $template['destination'] = $this->makeAbsolutePath($file, $template['destination']);

            return $template;
        }, $templates);
    }

    /**
     * @param string $absoluteOrRelativePath
     * @param string $baseFile
     * @return string
     * @throws \InvalidArgumentException
     */
    private function fixPath(string $absoluteOrRelativePath, string $baseFile): string
    {
        $possiblyValidFiles = [
            $this->applicationRootDirectory . '/' . $absoluteOrRelativePath,
            $this->makeAbsolutePath($baseFile, $absoluteOrRelativePath),
            $absoluteOrRelativePath,
        ];

        foreach ($possiblyValidFiles as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Unable to find a file referenced by "%s", tried: %s',
            $absoluteOrRelativePath,
            print_r($possiblyValidFiles, true)
        ));
    }

    /**
     * @param string $baseFile
     * @param string $path
     * @return string
     */
    private function makeAbsolutePath(string $baseFile, string $path): string
    {
        return pathinfo($baseFile, PATHINFO_DIRNAME) . '/' . $path;
    }
}
