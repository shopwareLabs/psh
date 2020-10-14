<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use function array_slice;
use function count;
use function explode;
use function mb_strpos;
use function mb_strtoupper;
use function mb_substr;
use function sprintf;
use function str_replace;

class ParameterParser
{
    public function parseParams(array $params): array
    {
        if (count($params) < 2) {
            return [];
        }

        $params = array_slice($params, 2);

        $reformattedParams = [];
        $paramsCount = count($params);
        for ($i = 0; $i < $paramsCount; $i++) {
            $key = $params[$i];

            $this->testParameterFormat($key);

            if ($this->isKeyValuePair($key)) {
                list($key, $value) = explode('=', $key, 2);

                if ($this->isEnclosedInAmpersand($value)) {
                    $value = mb_substr($value, 1, -1);
                }
            } else {
                $i++;
                $value = $params[$i];
            }

            $key = str_replace('--', '', $key);
            $reformattedParams[mb_strtoupper($key)] = $value;
        }

        return $reformattedParams;
    }

    /**
     * @param $key
     */
    private function testParameterFormat(string $key)
    {
        if (mb_strpos($key, '--') !== 0) {
            throw new InvalidParameterException(
                sprintf('Unable to parse parameter %s. Use -- for correct usage', $key)
            );
        }
    }

    /**
     * @param $key
     * @return bool|int
     */
    private function isKeyValuePair($key)
    {
        return mb_strpos($key, '=');
    }

    /**
     * @param $value
     */
    private function isEnclosedInAmpersand($value): bool
    {
        return mb_strpos($value, '"') === 0;
    }
}
