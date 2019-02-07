<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

use DOMElement;
use DOMXPath;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Load the config data from an xml file
 */
class XmlConfigFileLoader extends ConfigFileLoader
{
    const NODE_HEADER = 'header';

    const NODE_PLACEHOLDER = 'placeholder';

    const NODE_PLACEHOLDER_DYNAMIC = 'dynamic';

    const NODE_PLACEHOLDER_CONST = 'const';

    const NODE_PATH = 'path';

    const NODE_ENVIRONMENT = 'environment';

    const NODE_TEMPLATE = 'template';

    const NODE_TEMPLATE_SOURCE = 'source';

    const NODE_TEMPLATE_DESTINATION = 'destination';

    /**
     * @var ConfigBuilder
     */
    private $configBuilder;

    /**
     * @var string
     */
    private $applicationRootDirectory;

    /**
     * @param ConfigBuilder $configBuilder
     * @param string $applicationRootDirectory
     */
    public function __construct(ConfigBuilder $configBuilder, string $applicationRootDirectory)
    {
        $this->configBuilder = $configBuilder;
        $this->applicationRootDirectory = $applicationRootDirectory;
    }

    /**
     * @inheritdoc
     */
    public function isSupported(string $file): bool
    {
        return in_array(pathinfo($file, PATHINFO_BASENAME), ['.psh.xml', '.psh.xml.dist', '.psh.xml.override'], true);
    }

    /**
     * @inheritdoc
     */
    public function load(string $file, array $params): Config
    {
        $pshConfigNode = $this->loadXmlRoot($file);
        $this->configBuilder->start();

        try {
            $this->configBuilder->setHeader(
                $this->extractNode(self::NODE_HEADER, $pshConfigNode)->nodeValue
            );
        } catch (\InvalidArgumentException $e) {
        }

        $this->setConfigData($file, $pshConfigNode);

        $environments = $this->extractNodes(self::NODE_ENVIRONMENT, $pshConfigNode);

        foreach ($environments as $node) {
            $this->configBuilder->start($node->getAttribute('name'));
            $this->setConfigData($file, $node);
        }

        return $this->configBuilder
            ->create($params);
    }

    /**
     * @param string $file
     * @param array $pshConfigNode
     */
    private function setConfigData(string $file, DOMElement $pshConfigNode)
    {
        $this->configBuilder->setCommandPaths(
            $this->extractCommandPaths($file, $pshConfigNode)
        );

        try {
            $this->extractPlaceholders(
                $this->extractNode(self::NODE_PLACEHOLDER, $pshConfigNode)
            );
        } catch (\InvalidArgumentException $e) {
            // nth
        }

        $this->configBuilder->setTemplates(
            $this->extractTemplates($file, $pshConfigNode)
        );
    }

    /**
     * @param string $key
     * @param DOMElement $parent
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
     * @param string $key
     * @param DOMElement $parent
     * @return DOMElement
     */
    private function extractNode(string $key, DOMElement $parent): DOMElement
    {
        $nodes = $this->extractNodes($key, $parent);

        if (count($nodes) !== 1) {
            throw new \InvalidArgumentException('No node found for ' . $key);
        }

        return $nodes[0];
    }

    /**
     * @param string $file
     * @param $pshConfigNode
     * @return array
     */
    private function extractCommandPaths(string $file, DOMElement $pshConfigNode): array
    {
        $pathNodes = $this->extractNodes(self::NODE_PATH, $pshConfigNode);

        return array_map(function (DOMElement $path) use ($file) {
            return $this->fixPath($this->applicationRootDirectory, $path->nodeValue, $file);
        }, $pathNodes);
    }

    /**
     * @param string $file
     * @param array $pshConfigNodes
     * @return array
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
                )
            ];
        }, $templates);
    }

    /**
     * @param DOMElement $placeholder
     */
    private function extractPlaceholders(DOMElement $placeholder)
    {
        foreach ($this->extractNodes(self::NODE_PLACEHOLDER_DYNAMIC, $placeholder) as $dynamic) {
            $this->configBuilder->setDynamicVariable($dynamic->getAttribute('name'), $dynamic->nodeValue);
        }

        foreach ($this->extractNodes(self::NODE_PLACEHOLDER_CONST, $placeholder) as $dynamic) {
            $this->configBuilder->setConstVariable($dynamic->getAttribute('name'), $dynamic->nodeValue);
        }
    }

    /**
     * @param string $file
     * @return DOMElement
     */
    private function loadXmlRoot(string $file): DOMElement
    {
        $xml = XmlUtils::loadFile($file, __DIR__ . '/../../resource/config.xsd');
        $xPath = new DOMXPath($xml);

        /** @var \DOMNodeList $pshConfigNodes */
        $pshConfigNodes = $xPath->query('//psh');

        return $pshConfigNodes[0];
    }
}
