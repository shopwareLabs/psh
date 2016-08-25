<?php declare (strict_types = 1);

namespace Shopware\Psh\Listing;

/**
 * Load all scripts from all the supplied paths and create an array of scripts
 */
class ScriptFinder
{
    const VALID_EXTENSIONS = [
        'sh',
        'psh'
    ];

    /**
     * @var string[]
     */
    private $scriptPaths;

    /**
     * @param string[] $scriptPaths
     */
    public function __construct(array $scriptPaths)
    {
        $this->scriptPaths = $scriptPaths;
    }

    /**
     * @return Script[]
     */
    public function getAllScripts(): array
    {
        $scripts = [];

        foreach ($this->scriptPaths as $pathNamespace => $path) {
            foreach (scandir($path) as $fileName) {
                $scriptNamespace = null;

                if (strpos($fileName, '.') === 0) {
                    continue;
                }

                $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                if (!in_array($extension, self::VALID_EXTENSIONS)) {
                    continue;
                }

                if (!is_numeric($pathNamespace)) {
                    $scriptNamespace = $pathNamespace;
                }

                $scripts[] = new Script($path, $fileName, $scriptNamespace);
            }
        }

        return $scripts;
    }

    /**
     * @param string $scriptName
     * @return Script
     */
    public function findScriptByName(string $scriptName): Script
    {
        foreach ($this->getAllScripts() as $script) {
            if ($script->getName() === $scriptName) {
                return $script;
            }
        }

        throw new ScriptNotFoundException('Unable to find script named "' . $scriptName . '"');
    }
}
