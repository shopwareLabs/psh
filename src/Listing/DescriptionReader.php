<?php


namespace Shopware\Psh\Listing;

class DescriptionReader
{
    const KEY_WORD = 'DESCRIPTION:';

    /**
     * @param array $lines
     * @return string
     */
    public function read(array $lines)
    {
        foreach ($lines as $line) {
            if (
                strpos($line, self::KEY_WORD) !== false &&
                strpos($line, '#') !== false
            ) {
                $result = substr($line, strlen(self::KEY_WORD) + strpos($line, self::KEY_WORD));
                return trim($result);
            }
        }

        return '';
    }
}
