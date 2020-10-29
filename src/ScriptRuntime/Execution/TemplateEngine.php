<?php declare(strict_types=1);

namespace Shopware\Psh\ScriptRuntime\Execution;

use RuntimeException;
use Shopware\Psh\Config\ValueProvider;
use function array_keys;
use function mb_strtoupper;
use function mb_substr;
use function preg_match_all;
use function str_replace;

/**
 * Replace the placeholders with there values
 */
class TemplateEngine
{
    const REGEX = '/__[A-Z0-9,_,-]+?__(?!\(sic\!\))/';

    const REGEX_SIC = '/__[A-Z0-9,_,-]+?__(\(sic\!\))/';

    /**
     * @param ValueProvider[] $allValues
     */
    public function render(string $shellCommand, array $allValues): string
    {
        preg_match_all(self::REGEX, $shellCommand, $matches);
        $placeholders = $matches[0];

        foreach ($placeholders as $match) {
            $shellCommand = str_replace($match, $this->getValue($match, $allValues), $shellCommand);
        }

        preg_match_all(self::REGEX_SIC, $shellCommand, $matches);
        $escapedPlaceholders = $matches[0];

        foreach ($escapedPlaceholders as $match) {
            $shellCommand = str_replace($match, str_replace('(sic!)', '', $match), $shellCommand);
        }

        return $shellCommand;
    }

    /**
     * @throws RuntimeException
     */
    private function getValue(string $placeholder, array $allValues): string
    {
        $valueName = mb_substr($placeholder, 2, -2);

        foreach (array_keys($allValues) as $key) {
            if (mb_strtoupper($key) === $valueName) {
                return $allValues[$key]->getValue();
            }
        }

        throw new RuntimeException('Missing required value for "' . $valueName . '"');
    }
}
