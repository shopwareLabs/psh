<?php declare(strict_types = 1);

namespace Shopware\Psh\Listing;

use Symfony\Component\Finder\Finder;

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

        foreach($this->scriptPaths as $path) {
            foreach(scandir($path) as $fileName) {
                if(strpos($fileName, '.') === 0) {
                    continue;
                }

                $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                if(!in_array($extension, self::VALID_EXTENSIONS)) {
                    continue;
                }

                $scripts[] = new Script($path, $fileName);
            }
        }

        return $scripts;
    }

    public function findScriptByName(string $scriptName): Script
    {
        foreach($this->getAllScripts() as $script) {
            if($script->getName() === $scriptName) {
                return $script;
            }
        }

        throw new \RuntimeException('Unable to find script named "' . $scriptName . '"');
    }
}