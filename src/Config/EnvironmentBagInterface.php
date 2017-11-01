<?php declare(strict_types=1);

namespace Shopware\Psh\Config;

/**
 * A single configuration environment
 */
interface EnvironmentBagInterface
{
    /**
     * @return array
     */
    public function getAllScriptPaths(): array;

    /**
     * @return array
     */
    public function getDynamicVariables(): array;

    /**
     * @return array
     */
    public function getConstants(): array;

    /**
     * @return array
     */
    public function getTemplates(): array;
}
