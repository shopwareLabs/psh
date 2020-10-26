<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use Symfony\Component\Yaml\Parser;
use function array_key_exists;
use function array_map;
use function in_array;
use function pathinfo;

/**
 * Load the config data from a yaml file
 */
class YamlConfigFileLoader implements ConfigFileLoader
{
    use ConfigFileLoaderFileSystemHandlers;

    const KEY_HEADER = 'header';

    const KEY_DYNAMIC_VARIABLES = 'dynamic';

    const KEY_CONST_VARIABLES = 'const';

    const KEY_DOTENV_PATHS = 'dotenv';

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

    public function __construct(Parser $yamlReader, ConfigBuilder $configBuilder, string $applicationRootDirectory)
    {
        $this->yamlReader = $yamlReader;
        $this->configBuilder = $configBuilder;
        $this->applicationRootDirectory = $applicationRootDirectory;
    }

    public function isSupported(string $file): bool
    {
        return in_array(pathinfo($file, PATHINFO_BASENAME), ['.psh.yaml', '.psh.yml', '.psh.yml.dist', '.psh.yml.override', '.psh.yaml.dist', '.psh.yaml.override'], true);
    }

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

    private function setConfigData(string $file, array $rawConfigData): void
    {
        $this->configBuilder->setCommandPaths(
            $this->extractPaths($file, $rawConfigData, self::KEY_COMMAND_PATHS)
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

        $this->configBuilder->setDotenvPaths(
            $this->extractPaths($file, $rawConfigData, self::KEY_DOTENV_PATHS)
        );
    }

    /**
     * @param mixed|null $default
     * @return mixed|null
     */
    private function extractData(string $key, array $rawConfig, $default = false)
    {
        if (!array_key_exists($key, $rawConfig)) {
            return $default;
        }

        return $rawConfig[$key];
    }

    private function parseFileContents(string $contents): array
    {
        return $this->yamlReader->parse($contents);
    }

    private function extractPaths(string $file, array $rawConfigData, string $key): array
    {
        $paths = $this->extractData($key, $rawConfigData, []);

        return array_map(function ($path) use ($file) {
            return $this->fixPath($this->applicationRootDirectory, $path, $file);
        }, $paths);
    }

    private function extractTemplates(string $file, array $rawConfigData): array
    {
        $templates = $this->extractData(self::KEY_TEMPLATES, $rawConfigData, []);

        return array_map(function ($template) use ($file) {
            $template['source'] = $this->fixPath($this->applicationRootDirectory, $template['source'], $file);
            $template['destination'] = $this->makeAbsolutePath($file, $template['destination']);

            return $template;
        }, $templates);
    }
}
