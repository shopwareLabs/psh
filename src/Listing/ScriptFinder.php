<?php declare(strict_types=1);


namespace Shopware\Psh\Listing;

use Shopware\Psh\Config\ScriptPath;

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
     * @var ScriptPath[]
     */
    private $scriptPaths;

    /**
     * @var DescriptionReader
     */
    private $scriptDescriptionReader;

    /**
     * @param ScriptPath[] $scriptPaths
     * @param DescriptionReader $scriptDescriptionReader
     */
    public function __construct(array $scriptPaths, DescriptionReader $scriptDescriptionReader)
    {
        $this->scriptPaths = $scriptPaths;
        $this->scriptDescriptionReader = $scriptDescriptionReader;
    }

    /**
     * @return Script[]
     * @throws ScriptPathNotValidException
     */
    public function getAllScripts(): array
    {
        $scripts = [];

        foreach ($this->scriptPaths as $path) {
            if (!is_dir($path->getPath())) {
                throw new ScriptPathNotValidException("The given script path: '{$path->getPath()}' is not a valid directory");
            }

            foreach (scandir($path->getPath()) as $fileName) {
                if (strpos($fileName, '.') === 0) {
                    continue;
                }

                $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                if (!in_array($extension, self::VALID_EXTENSIONS)) {
                    continue;
                }

                $description = $this->scriptDescriptionReader->read($path->getPath() . '/' . $fileName);

                $newScript = new Script($path->getPath(), $fileName, $path->getNamespace(), $description);

                $scripts[$newScript->getName()] = $newScript;
            }
        }

        return $scripts;
    }

    /**
     * @param string $query
     * @return array
     */
    public function findScriptsByPartialName(string $query): array
    {
        $scripts = $this->getAllScripts();

        return array_filter($scripts, function ($key) use ($query) {
            return strpos($key, $query) > -1;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param string $scriptName
     * @return Script
     * @throws ScriptNotFoundException
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
