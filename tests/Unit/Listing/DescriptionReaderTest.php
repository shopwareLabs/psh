<?php declare(strict_types=1);

namespace Shopware\Psh\Test\Unit\Listing;

use Shopware\Psh\Listing\DescriptionReader;

class DescriptionReaderTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_can_be_created()
    {
        $this->assertInstanceOf(DescriptionReader::class, new DescriptionReader());
    }

    public function test_it_reads_description_with_hashtag()
    {
        $reader = new DescriptionReader();
        $result = $reader->read([ '#DESCRIPTION: My desc.' ]);
        $this->assertSame('My desc.', $result);
    }

    public function test_it_should_not_read_a_description()
    {
        $reader = new DescriptionReader();
        $result = $reader->read([ '', 'some content' ]);
        $this->assertSame('', $result);
    }

    public function test_it_should_not_parse_description_if_is_not_in_a_comment()
    {
        $reader = new DescriptionReader();
        $result = $reader->read([ 'some line', 'DESCRIPTION: My desc.' ]);
        $this->assertSame('', $result);
    }

    public function test_it_should_remove_whitespace_characters()
    {
        $reader = new DescriptionReader();
        $result = $reader->read([ ' # DESCRIPTION: My desc.  ' ]);
        $this->assertSame('My desc.', $result);
    }
}
