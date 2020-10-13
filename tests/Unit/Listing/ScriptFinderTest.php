<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Listing;

use PHPUnit\Framework\TestCase;
use Shopware\Psh\Listing\DescriptionReader;
use Shopware\Psh\Listing\ScriptFinder;

class ScriptFinderTest extends TestCase
{
    public function test_script_finder_holds_contract_if_no_paths_present()
    {
        $finder = new ScriptFinder([], new DescriptionReader());
        $this->assertInstanceOf(ScriptFinder::class, $finder);
        $this->assertInternalType('array', $finder->getAllScripts());
    }
}
