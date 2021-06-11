<?php declare(strict_types=1);

namespace Shopware\Psh\Listing;

use Shopware\Psh\Config\ScriptsPath;
use function array_filter;
use function in_array;
use function levenshtein;
use function mb_strpos;
use function pathinfo;
use function scandir;

/**
 * Load all scripts from all the supplied paths and create an array of scripts
 */
class ScriptFinder
{
    private const VALID_EXTENSIONS = [
        'sh',
        'psh',
    ];

    /**
     * @var ScriptsPath[]
     */
    private $scriptsPaths;

    /**
     * @var DescriptionReader
     */
    private $scriptDescriptionReader;

    /**
     * @param ScriptsPath[] $scriptsPaths
     */
    public function __construct(array $scriptsPaths, DescriptionReader $scriptDescriptionReader)
    {
        $this->scriptsPaths = $scriptsPaths;
        $this->scriptDescriptionReader = $scriptDescriptionReader;
    }

    /**
     * @throws ScriptPathNotValid
     * @return Script[]
     */
    public function getAllScripts(): array
    {
        $scripts = [];

        foreach ($this->scriptsPaths as $path) {
            $this->testPathValidity($path);

            foreach (scandir($path->getPath(), SCANDIR_SORT_ASCENDING) as $fileName) {
                if (!$this->isValidScript($fileName)) {
                    continue;
                }

                $description = $this->scriptDescriptionReader->read($path->getPath() . '/' . $fileName);
                $newScript = new Script($path->getPath(), $fileName, $path->isHidden(), $path->getWorkingDirectory(), $path->getNamespace(), $description);

                $scripts[$newScript->getName()] = $newScript;
            }
        }

        return $scripts;
    }

    public function getAllVisibleScripts(): array
    {
        return array_filter($this->getAllScripts(), static function (Script $script): bool {
            return !$script->isHidden();
        });
    }

    public function findScriptsByPartialName(string $query): array
    {
        $scripts = $this->getAllVisibleScripts();

        return array_filter($scripts, static function ($key) use ($query) {
            return mb_strpos($key, $query) !== false || levenshtein($key, $query) < 3;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param string[] $scriptNames
     * @return Script[]
     */
    public function findScriptsInOrder(array $scriptNames): array
    {
        $scripts = [];
        foreach ($scriptNames as $scriptName) {
            $scripts[] = $this->findScriptByName($scriptName);
        }

        return $scripts;
    }

    /**
     * @throws ScriptNotFound
     */
    public function findScriptByName(string $scriptName): Script
    {
        foreach ($this->getAllScripts() as $script) {
            if ($script->getName() === $scriptName) {
                return $script;
            }
        }

        throw (new ScriptNotFound('Unable to find script named "' . $scriptName . '"'))->setScriptName($scriptName);
    }

    /**
     * @param $fileName
     */
    private function isValidScript(string $fileName): bool
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return in_array($extension, self::VALID_EXTENSIONS, true);
    }

    private function testPathValidity(ScriptsPath $path): void
    {
        if (!$path->isValid()) {
            throw new ScriptPathNotValid("The given script path: '{$path->getPath()}' is not a valid directory");
        }
    }
}
