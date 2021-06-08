<?php declare(strict_types=1);

namespace Shopware\Psh\Application;

use function array_slice;
use function count;
use function explode;
use function mb_strpos;
use function mb_substr;
use function sprintf;
use function str_replace;


/**
 * ./psh --no-header unit --filter once
 */
class ParameterParser
{
    public function parseAllParams(array $params): RuntimeParameters
    {
        if (count($params) === 0) {
            return new RuntimeParameters(
                [],
                [],
                []
            );
        }

        $params = array_splice($params, 1);
        $commandsAreAt = 0;

        $appOptions = [];
        foreach($params as $commandsAreAt => $param) {
            if(!in_array($param, ApplicationOptions::getAllFlags())) {
                break;
            }

            $appOptions[] = $param;
            $commandsAreAt++;
        }

        $scriptNames = [[], $this->explodeScriptNames($params, $commandsAreAt)];
        $overwrites = [[], $this->extractParams(array_slice($params, $commandsAreAt + 1))];

        return new RuntimeParameters(
            array_unique($appOptions),
            array_merge(...$scriptNames),
            array_merge(...$overwrites)
        );
    }

    private function extractParams(array $params): array
    {
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
            $reformattedParams[$key] = $value;
        }

        return $reformattedParams;
    }

    private function testParameterFormat(string $key): void
    {
        if (mb_strpos($key, '--') !== 0) {
            throw new InvalidParameter(
                sprintf('Unable to parse parameter "%s". Use -- for correct usage', $key)
            );
        }
    }

    private function isKeyValuePair(string $key): bool
    {
        return mb_strpos($key, '=') !== false;
    }

    private function isEnclosedInAmpersand(string $value): bool
    {
        return mb_strpos($value, '"') === 0;
    }

    private function explodeScriptNames(array $params, int $position): array
    {
        if(!isset($params[$position])) {
            return [];
        }

        return explode(',', $params[$position]);
    }
}
