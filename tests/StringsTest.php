<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Utils\Strings;

class StringsTest extends BaseTest {
    /**
     * @test
     */
    public function can_check_html_tag() {
        $a = "<div>ciao</div>";
        $b = "< >";
        $c = "<day,month,year>";
        $d = "<a href='#'>";
        $e = "<h1>";
        $f = "<a href='#@,'>";
        $g = '<ph id=\"source1\" dataRef=\"source1\"/>';
        $h = '<trans-unit id="pendo-image-e3aaf7b7|alt">';
        $i = '<meta http-equiv="X-UA-Compatible" content="ie=edge"/>';

        $this->assertTrue( StringUtilsTestHelper::isHtmlString( $a ) );
        $this->assertFalse( StringUtilsTestHelper::isHtmlString( $b ) );
        $this->assertFalse( StringUtilsTestHelper::isHtmlString( $c ) );
        $this->assertTrue( StringUtilsTestHelper::isHtmlString( $d ) );
        $this->assertTrue( StringUtilsTestHelper::isHtmlString( $e ) );
        $this->assertTrue( StringUtilsTestHelper::isHtmlString( $f ) );
        $this->assertTrue( StringUtilsTestHelper::isHtmlString( $g ) );
        $this->assertTrue( StringUtilsTestHelper::isHtmlString( $h ) );
        $this->assertTrue( StringUtilsTestHelper::isHtmlString( $i ) );
    }

    /**
     * @test
     */
    public function can_get_the_last_character() {
        $phrase = 'Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola.';

        $this->assertEquals( '.', StringUtilsTestHelper::lastChar( $phrase ) );

        $phrase = 'Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola. ';

        $this->assertEquals( ' ', StringUtilsTestHelper::lastChar( $phrase ) );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function contains_function_can_discriminate_trailing_spaces() {
        $full   = 'Il naso evidenzia raffinati sentori floreali di rosa canina e violetta, frutti rossi croccanti tipo ribes e fragole di bosco, dopo i quali emergono cenni gentili di grafite e liquirizia. Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola. ';
        $phrase = 'Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola. ';

        $this->assertTrue( StringUtilsTestHelper::contains( $phrase, $full ) );

        $full   = 'Il naso evidenzia raffinati sentori floreali di rosa canina e violetta, frutti rossi croccanti tipo ribes e fragole di bosco, dopo i quali emergono cenni gentili di grafite e liquirizia. Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola.';
        $phrase = 'Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola. ';

        $this->assertFalse( StringUtilsTestHelper::contains( $phrase, $full ) );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function can_detected_escaped_html_entities() {
        $this->assertFalse( Strings::isADoubleEscapedEntity( "&lt;p class=&quot;cmln__paragraph&quot;&gt;" ) );
        $this->assertFalse( Strings::isADoubleEscapedEntity( "&lt;/p&gt;" ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( "&amp;#39;" ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( "&amp;amp;" ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( "&amp;apos;" ) );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function can_decode_only_escaped_entities() {
        $string   = "&lt;/p&gt; &amp;#39; &apos;";
        $expected = "&lt;/p&gt; &#39; &apos;";

        $this->assertEquals( Strings::htmlspecialchars_decode( $string, true ), $expected );

        $string   = "&amp;amp; &amp;apos;";
        $expected = "&amp; &apos;";

        $this->assertEquals( Strings::htmlspecialchars_decode( $string, true ), $expected );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function can_detect_escaped_html() {
        $strings = [
                '&lt;ph id="1" /&gt;',
                '&lt;div class="test"&gt;This is an html string &lt; /div&gt;',
        ];

        foreach ( $strings as $string ) {
            $this->assertTrue( StringUtilsTestHelper::isAnEscapedHTML( $string ) );
        }

        $strings = [
                '<ph id="1" />',
                '<div class="test">This is an html string < /div>',
        ];

        foreach ( $strings as $string ) {
            $this->assertFalse( StringUtilsTestHelper::isAnEscapedHTML( $string ) );
        }
    }

    /**
     * @test
     */
    public function can_detect_escaped_html_additional_test() {
        $string = '<5 &lt;pc id="1"/&gt;';

        $this->assertTrue( StringUtilsTestHelper::isAnEscapedHTML( $string ) );

        $string = '&lt;5 <pc id="1"/>';

        $this->assertFalse( StringUtilsTestHelper::isAnEscapedHTML( $string ) );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function can_detect_JSON() {
        $json = '{
            "key": "name",
            "key2": "name2",
            "key3": "name3"
        }';

        $jsonList = '[ "abc", "234", 456 ]';

        $notJson = "This is a sample text";

        $jsonStringButUnwanted = '"This is a sample text"';
        $jsonNumberButUnwanted = 222;
        $jsonBooleanButUnwanted = true;

        $this->assertFalse( Strings::isJSON( $notJson ) );
        $this->assertFalse( Strings::isJSON( $jsonStringButUnwanted ) );
        $this->assertFalse( Strings::isJSON( $jsonNumberButUnwanted ) );
        $this->assertFalse( Strings::isJSON( $jsonBooleanButUnwanted ) );
        $this->assertTrue( Strings::isJSON( $json ) );
        $this->assertTrue( Strings::isJSON( $jsonList ) );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function can_encode_json() {
        $json   = '{"source3":"&#39;","source4":"&lt;a class=&quot;cmln__link&quot; href=&quot;https:\\/\\/restaurant-dashboard.uber.com\\/&quot; target=&quot;_blank&quot;&gt;","source5":"&lt;\\/a&gt;","source1":"&lt;p class=&quot;cmln__paragraph&quot;&gt;","source6":"&lt;\\/p&gt;","source2":"&#39;"}';
        $noJson = "csacsacsa";

        $this->assertCount( 6, Strings::jsonToArray( $json ) );
        $this->assertEmpty( Strings::jsonToArray( $noJson ) );
    }

    /**
     * @test
     */
    public function can_fix_not_well_formed_xml() {
        $original = '<g id="1">Hello</g>, 4 > 3 -> <g id="1">Hello</g>, 4 &gt; 3';
        $expected = '<g id="1">Hello</g>, 4 &gt; 3 -&gt; <g id="1">Hello</g>, 4 &gt; 3';

        $this->assertEquals( $expected, Strings::fixNonWellFormedXml( $original ) );

        $original = '<mrk id="1">Test1</mrk><mrk id="2">Test2<ex id="1">Another Test Inside</ex></mrk><mrk id="3">Test3<a href="https://example.org">ClickMe!</a></mrk>';
        $expected = '<mrk id="1">Test1</mrk><mrk id="2">Test2<ex id="1">Another Test Inside</ex></mrk><mrk id="3">Test3&lt;a href="https://example.org"&gt;ClickMe!&lt;/a&gt;</mrk>';

        $this->assertEquals( $expected, Strings::fixNonWellFormedXml( $original ) );

        $tests = [
                ''                                                                                                   => '',
                '&#129305; Join this (video)call at: {{joinUrl}}'                                                    => '&#129305; Join this (video)call at: {{joinUrl}}',
                'just text'                                                                                          => 'just text',
                '<gap>Hey</gap>'                                                                                     => '&lt;gap&gt;Hey&lt;/gap&gt;',
                '<mrk>Hey</mrk>'                                                                                     => '<mrk>Hey</mrk>',
                '<g >Hey</g >'                                                                                       => '<g >Hey</g >',
                '<g    >Hey</g   >'                                                                                  => '<g    >Hey</g   >',
                '<g id="99">Hey</g>'                                                                                 => '<g id="99">Hey</g>',
                'Hey<x/>'                                                                                            => 'Hey<x/>',
                'Hey<x />'                                                                                           => 'Hey<x />',
                'Hey<x   />'                                                                                         => 'Hey<x   />',
                'Hey<x id="15"/>'                                                                                    => 'Hey<x id="15"/>',
                'Hey<bx id="1"/>'                                                                                    => 'Hey<bx id="1"/>',
                'Hey<ex id="1"/>'                                                                                    => 'Hey<ex id="1"/>',
                '<bpt id="1">Hey</bpt>'                                                                              => '<bpt id="1">Hey</bpt>',
                '<ept id="1">Hey</ept>'                                                                              => '<ept id="1">Hey</ept>',
                '<ph id="1">Hey</ph>'                                                                                => '<ph id="1">Hey</ph>',
                '<it id="1">Hey</it>'                                                                                => '<it id="1">Hey</it>',
                '<mrk mid="3" mtype="seg"><g id="2">Hey man! <x id="1"/><b id="dunno">Hey man & hey girl!</b></mrk>' => '<mrk mid="3" mtype="seg"><g id="2">Hey man! <x id="1"/>&lt;b id="dunno"&gt;Hey man &amp; hey girl!&lt;/b&gt;</mrk>',
        ];

        foreach ( $tests as $in => $expected ) {
            $out = Strings::fixNonWellFormedXml( $in );
            $this->assertEquals( $expected, $out );
        }
    }

    /**
     * @test
     */
    public function can_validate_an_uuid() {
        $not_valid_uuid = 'xxx';
        $uuid           = '4213862b-596b-4b03-b175-baf4a0ed6fd8';

        $this->assertFalse( Strings::isAValidUuid( $not_valid_uuid ) );
        $this->assertTrue( Strings::isAValidUuid( $uuid ) );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function get_the_number_of_trailing_spaces() {
        $string  = "La casa in campagna è bella  ";
        $string2 = "Dante Alighieri   ";
        $string3 = "Questa stringa non contiente spazi alla fine della frase.";
        $string4 = "Questa stringa non contiente uno spazio alla fine della frase. ";
        $string5 = "‫مرحبًا، أنا براين";
        $string6 = "‫أنا متحمس لمشاركة  ";

        $this->assertEquals( 2, Strings::getTheNumberOfTrailingSpaces( $string ) );
        $this->assertEquals( 3, Strings::getTheNumberOfTrailingSpaces( $string2 ) );
        $this->assertEquals( 0, Strings::getTheNumberOfTrailingSpaces( $string3 ) );
        $this->assertEquals( 1, Strings::getTheNumberOfTrailingSpaces( $string4 ) );
        $this->assertEquals( 0, Strings::getTheNumberOfTrailingSpaces( $string5 ) );
        $this->assertEquals( 2, Strings::getTheNumberOfTrailingSpaces( $string6 ) );
    }
}
