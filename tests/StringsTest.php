<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Utils\Strings;

class StringsTest extends BaseTest
{
    /**
     * @test
     * @throws \Exception
     */
    public function can_detect_JSON()
    {
        $json = '{
            "key": "name",
            "key2": "name2",
            "key3": "name3"
        }';

        $notJson = "This is a sample text";

        $this->assertFalse(Strings::isJSON($notJson));
        $this->assertTrue(Strings::isJSON($json));
    }

    /**
     * @test
     */
    public function can_fix_not_well_formed_xml()
    {
        $original = '<g id="1">Hello</g>, 4 > 3 -> <g id="1">Hello</g>, 4 &gt; 3';
        $expected = '<g id="1">Hello</g>, 4 &gt; 3 -&gt; <g id="1">Hello</g>, 4 &gt; 3';

        $this->assertEquals($expected, Strings::fixNonWellFormedXml($original));
    }

    /**
     * @test
     */
    public function can_extract_tags()
    {
        $xliff = '<notes>
                    <note id="n1">note for file.</note>
                </notes>';

        $this->assertCount(1, Strings::extractTag('notes', $xliff));

        $xliff = '<note id="n1">note for file.</note><note id="n2">note2 for file.</note><note id="n3">note2 for file.</note>';

        $this->assertCount(3, Strings::extractTag('note', $xliff));
    }

    /**
     * @test
     */
    public function can_split_tags()
    {
        $xliff = '<xliff><file>das</file><file>das2</file><file>das3</file></xliff>';

        $splitted = Strings::splitTag('file', $xliff);

        $this->assertEquals($splitted[0], '<xliff>');
        $this->assertEquals($splitted[1], 'das</file>');
        $this->assertEquals($splitted[2], 'das2</file>');
    }
}



