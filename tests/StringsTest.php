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

        $original = '<mrk id="1">Test1</mrk><mrk id="2">Test2<ex id="1">Another Test Inside</ex></mrk><mrk id="3">Test3<a href="https://example.org">ClickMe!</a></mrk>';
        $expected = '<mrk id="1">Test1</mrk><mrk id="2">Test2<ex id="1">Another Test Inside</ex></mrk><mrk id="3">Test3&lt;a href="https://example.org"&gt;ClickMe!&lt;/a&gt;</mrk>';

        $this->assertEquals($expected, Strings::fixNonWellFormedXml($original));

        $tests = array(
                '' => '',
                'just text' => 'just text',
                '<gap>Hey</gap>' => '&lt;gap&gt;Hey&lt;/gap&gt;',
                '<mrk>Hey</mrk>' => '<mrk>Hey</mrk>',
                '<g >Hey</g >' => '<g >Hey</g >',
                '<g    >Hey</g   >' => '<g    >Hey</g   >',
                '<g id="99">Hey</g>' => '<g id="99">Hey</g>',
                'Hey<x/>' => 'Hey<x/>',
                'Hey<x />' => 'Hey<x />',
                'Hey<x   />' => 'Hey<x   />',
                'Hey<x id="15"/>' => 'Hey<x id="15"/>',
                'Hey<bx id="1"/>' => 'Hey<bx id="1"/>',
                'Hey<ex id="1"/>' => 'Hey<ex id="1"/>',
                '<bpt id="1">Hey</bpt>' => '<bpt id="1">Hey</bpt>',
                '<ept id="1">Hey</ept>' => '<ept id="1">Hey</ept>',
                '<ph id="1">Hey</ph>' => '<ph id="1">Hey</ph>',
                '<it id="1">Hey</it>' => '<it id="1">Hey</it>',
                '<mrk mid="3" mtype="seg"><g id="2">Hey man! <x id="1"/><b id="dunno">Hey man & hey girl!</b></mrk>' => '<mrk mid="3" mtype="seg"><g id="2">Hey man! <x id="1"/>&lt;b id="dunno"&gt;Hey man &amp; hey girl!&lt;/b&gt;</mrk>',
        );

        foreach ($tests as $in => $expected) {
            $out = Strings::fixNonWellFormedXml($in);
            $this->assertEquals( $expected, $out );
        }
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
