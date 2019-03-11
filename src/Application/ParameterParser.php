<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

class ParameterParser
{
    /**
     * @param array $params
     * @return array
     */
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
                    $value = substr($value, 1, -1);
                }
            } else {
                $i++;
                $value = $params[$i];
            }

            $key = str_replace('--', '', $key);
            $reformattedParams[strtoupper($key)] = $value;
        }

        return $reformattedParams;
    }


    /**
     * @param $key
     */
    private function testParameterFormat(string $key)
    {
        if (strpos($key, '--') !== 0) {
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
        return strpos($key, '=');
    }

    /**
     * @param $value
     * @return bool
     */
    private function isEnclosedInAmpersand($value): bool
    {
        return strpos($value, '"') === 0;
    }
}
