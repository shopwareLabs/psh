<?php declare(strict_types=1);

namespace Shopware\Psh\Listing;

use function file;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function trim;

class DescriptionReader
{
    private const KEY_WORD = 'DESCRIPTION:';

    public function read(string $filePath): string
    {
        foreach (file($filePath) as $line) {
            if (
                mb_strpos($line, self::KEY_WORD) !== false &&
                mb_strpos($line, '#') !== false
            ) {
                $result = mb_substr($line, mb_strlen(self::KEY_WORD) + mb_strpos($line, self::KEY_WORD));

                return trim($result);
            }
        }

        return '';
    }
}
