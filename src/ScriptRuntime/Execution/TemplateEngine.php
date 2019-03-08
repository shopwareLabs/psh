<?php declare(strict_types=1);


namespace Shopware\Psh\ScriptRuntime\Execution;

/**
 * Replace the placeholders with there values
 */
class TemplateEngine
{
    const REGEX = '/__[A-Z0-9,_,-]+?__(?!\(sic\!\))/';

    const REGEX_SIC = '/__[A-Z0-9,_,-]+?__(\(sic\!\))/';

    /**
     * @param string $shellCommand
     * @param ValueProvider[] $allValues
     * @return string
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
     * @param string $placeholder
     * @param array $allValues
     * @return mixed
     * @throws \RuntimeException
     */
    private function getValue(string $placeholder, array $allValues)
    {
        $valueName = substr($placeholder, 2, -2);

        foreach (array_keys($allValues) as $key) {
            if (strtoupper($key) === $valueName) {
                return $allValues[$key]->getValue();
            }
        }

        throw new \RuntimeException('Missing required value for "' . $valueName . '"');
    }
}
