<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use DOMElement;
use DOMNodeList;
use DOMXPath;
use Symfony\Component\Config\Util\XmlUtils;
use function array_map;
use function count;
use function in_array;
use function pathinfo;

/**
 * Load the config data from an xml file
 */
class XmlConfigFileLoader extends ConfigFileLoader
{
    private const NODE_HEADER = 'header';

    private const NODE_PLACEHOLDER = 'placeholder';

    private const NODE_PLACEHOLDER_DYNAMIC = 'dynamic';

    private const NODE_PLACEHOLDER_CONST = 'const';

    private const NODE_PLACEHOLDER_DOTENV = 'dotenv';

    private const NODE_PLACEHOLDER_REQUIRE = 'require';

    private const NODE_PATH = 'path';

    private const NODE_ENVIRONMENT = 'environment';

    private const NODE_TEMPLATE = 'template';

    private const NODE_TEMPLATE_SOURCE = 'source';

    private const NODE_TEMPLATE_DESTINATION = 'destination';

    /**
     * @var ConfigBuilder
     */
    private $configBuilder;

    /**
     * @var string
     */
    private $applicationRootDirectory;

    public function __construct(ConfigBuilder $configBuilder, string $applicationRootDirectory)
    {
        $this->configBuilder = $configBuilder;
        $this->applicationRootDirectory = $applicationRootDirectory;
    }

    public function isSupported(string $file): bool
    {
        return in_array(pathinfo($file, PATHINFO_BASENAME), ['.psh.xml', '.psh.xml.dist', '.psh.xml.override'], true);
    }

    public function load(string $file, array $params): Config
    {
        $pshConfigNode = $this->loadXmlRoot($file);
        $this->configBuilder->start();

        $headers = $this->extractNodes(self::NODE_HEADER, $pshConfigNode);

        foreach ($headers as $header) {
            $this->configBuilder
                ->setHeader($header->nodeValue);
        }

        $this->setConfigData($file, $pshConfigNode);

        $environments = $this->extractNodes(self::NODE_ENVIRONMENT, $pshConfigNode);

        foreach ($environments as $node) {
            $this->configBuilder->start($node->getAttribute('name'));
            $this->configBuilder->setHidden('true' === $node->getAttribute('hidden'));
            $this->setConfigData($file, $node);
        }

        return $this->configBuilder
            ->create($params);
    }

    /**
     * @param array $pshConfigNode
     */
    private function setConfigData(string $file, DOMElement $pshConfigNode)
    {
        $this->configBuilder->setCommandPaths(
            $this->extractCommandPaths($file, $pshConfigNode)
        );

        $placeholders = $this->extractNodes(self::NODE_PLACEHOLDER, $pshConfigNode);

        foreach ($placeholders as $placeholder) {
            $this->extractPlaceholders($file, $placeholder);
        }

        $this->configBuilder->setTemplates(
            $this->extractTemplates($file, $pshConfigNode)
        );
    }

    /**
     * @return DOMElement[]
     */
    private function extractNodes(string $key, DOMElement $parent): array
    {
        $nodes = [];

        foreach ($parent->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && $childNode->localName === $key) {
                $nodes[] = $childNode;
            }
        }

        if (count($nodes) === 0) {
            return [];
        }

        return $nodes;
    }

    /**
     * @param $pshConfigNode
     */
    private function extractCommandPaths(string $file, DOMElement $pshConfigNode): array
    {
        $pathNodes = $this->extractNodes(self::NODE_PATH, $pshConfigNode);

        return array_map(function (DOMElement $path) use ($file) {
            return $this->fixPath($this->applicationRootDirectory, $path->nodeValue, $file);
        }, $pathNodes);
    }

    /**
     * @param array $pshConfigNodes
     */
    private function extractTemplates(string $file, DOMElement $pshConfigNodes): array
    {
        $templates = $this->extractNodes(self::NODE_TEMPLATE, $pshConfigNodes);

        return array_map(function (DOMElement $template) use ($file) {
            return [
                'source' => $this->fixPath(
                    $this->applicationRootDirectory,
                    $template->getAttribute(self::NODE_TEMPLATE_SOURCE),
                    $file
                ),
                'destination' => $this->makeAbsolutePath(
                    $file,
                    $template->getAttribute(self::NODE_TEMPLATE_DESTINATION)
                ),
            ];
        }, $templates);
    }

    private function extractPlaceholders(string $file, DOMElement $placeholder)
    {
        foreach ($this->extractNodes(self::NODE_PLACEHOLDER_DYNAMIC, $placeholder) as $dynamic) {
            $this->configBuilder->setDynamicVariable($dynamic->getAttribute('name'), $dynamic->nodeValue);
        }

        foreach ($this->extractNodes(self::NODE_PLACEHOLDER_CONST, $placeholder) as $const) {
            $this->configBuilder->setConstVariable($const->getAttribute('name'), $const->nodeValue);
        }

        foreach ($this->extractNodes(self::NODE_PLACEHOLDER_DOTENV, $placeholder) as $dotenv) {
            $this->configBuilder->setDotenvPath($this->fixPath($this->applicationRootDirectory, $dotenv->nodeValue, $file));
        }

        foreach ($this->extractNodes(self::NODE_PLACEHOLDER_REQUIRE, $placeholder) as $require) {
            $this->configBuilder->setRequirePlaceholder($require->getAttribute('name'), $require->getAttribute('description'));
        }
    }

    private function loadXmlRoot(string $file): DOMElement
    {
        $xml = XmlUtils::loadFile($file, __DIR__ . '/../../resource/config.xsd');
        $xPath = new DOMXPath($xml);

        /** @var DOMNodeList $pshConfigNodes */
        $pshConfigNodes = $xPath->query('//psh');

        return $pshConfigNodes[0];
    }
}
