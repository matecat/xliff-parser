<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Exception;
use Matecat\XliffParser\Utils\Strings;
use PHPUnit\Framework\Attributes\Test;

class StringsTest extends Base {
    #[Test]
    public function can_check_html_tag(): void {
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

    #[Test]
    public function can_get_the_last_character(): void {
        $phrase = 'Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola.';

        $this->assertEquals( '.', StringUtilsTestHelper::lastChar( $phrase ) );

        $phrase = 'Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola. ';

        $this->assertEquals( ' ', StringUtilsTestHelper::lastChar( $phrase ) );
    }

    #[Test]
    public function contains_function_can_discriminate_trailing_spaces(): void {
        $full   = 'Il naso evidenzia raffinati sentori floreali di rosa canina e violetta, frutti rossi croccanti tipo ribes e fragole di bosco, dopo i quali emergono cenni gentili di grafite e liquirizia. Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola. ';
        $phrase = 'Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola. ';

        $this->assertTrue( StringUtilsTestHelper::contains( $phrase, $full ) );

        $full   = 'Il naso evidenzia raffinati sentori floreali di rosa canina e violetta, frutti rossi croccanti tipo ribes e fragole di bosco, dopo i quali emergono cenni gentili di grafite e liquirizia. Si presenta con una nuance rubino intensa e compatta dai luminosi riflessi viola.';

        $this->assertFalse( StringUtilsTestHelper::contains( $phrase, $full ) );
    }

    #[Test]
    public function can_detected_escaped_html_entities(): void {
        $this->assertFalse( Strings::isADoubleEscapedEntity( "&lt;p class=&quot;cmln__paragraph&quot;&gt;" ) );
        $this->assertFalse( Strings::isADoubleEscapedEntity( "&lt;/p&gt;" ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( "&amp;#39;" ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( "&amp;amp;" ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( "&amp;apos;" ) );
    }

    #[Test]
    public function can_decode_only_escaped_entities(): void {
        $string   = "&lt;/p&gt; &amp;#39; &apos;";
        $expected = "&lt;/p&gt; &#39; &apos;";

        $this->assertEquals( Strings::htmlspecialchars_decode( $string, true ), $expected );

        $string   = "&amp;amp; &amp;apos;";
        $expected = "&amp; &apos;";

        $this->assertEquals( Strings::htmlspecialchars_decode( $string, true ), $expected );
    }

    #[Test]
    public function can_detect_escaped_html(): void {
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

    #[Test]
    public function can_detect_escaped_html_additional_test(): void {
        $string = '<5 &lt;pc id="1"/&gt;';

        $this->assertTrue( StringUtilsTestHelper::isAnEscapedHTML( $string ) );

        $string = '&lt;5 <pc id="1"/>';

        $this->assertFalse( StringUtilsTestHelper::isAnEscapedHTML( $string ) );
    }

    #[Test]
    public function can_detect_JSON(): void {
        $json = '{
            "key": "name",
            "key2": "name2",
            "key3": "name3"
        }';

        $jsonList = '[ "abc", "234", 456 ]';

        $notJson = "This is a sample text";

        $jsonStringButUnwanted = '"This is a sample text"';
        $jsonNumberButUnwanted = '222';
        $jsonBooleanButUnwanted = 'true';

        $this->assertFalse( Strings::isJSON( $notJson ) );
        $this->assertFalse( Strings::isJSON( $jsonStringButUnwanted ) );
        $this->assertFalse( Strings::isJSON( $jsonNumberButUnwanted ) );
        $this->assertFalse( Strings::isJSON( $jsonBooleanButUnwanted ) );
        $this->assertTrue( Strings::isJSON( $json ) );
        $this->assertTrue( Strings::isJSON( $jsonList ) );
    }

    #[Test]
    public function can_encode_json(): void {
        $json   = '{"source3":"&#39;","source4":"&lt;a class=&quot;cmln__link&quot; href=&quot;https:\\/\\/restaurant-dashboard.uber.com\\/&quot; target=&quot;_blank&quot;&gt;","source5":"&lt;\\/a&gt;","source1":"&lt;p class=&quot;cmln__paragraph&quot;&gt;","source6":"&lt;\\/p&gt;","source2":"&#39;"}';
        $noJson = "csacsacsa";

        $this->assertCount( 6, Strings::jsonToArray( $json ) );
        $this->assertEmpty( Strings::jsonToArray( $noJson ) );
    }

    #[Test]
    public function can_fix_not_well_formed_xml(): void {
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

    #[Test]
    public function can_validate_an_uuid(): void {
        $not_valid_uuid = 'xxx';
        $uuid           = '4213862b-596b-4b03-b175-baf4a0ed6fd8';

        $this->assertFalse( Strings::isAValidUuidV4( $not_valid_uuid ) );
        $this->assertTrue( Strings::isAValidUuidV4( $uuid ) );
    }

    #[Test]
    public function get_the_number_of_trailing_spaces(): void {
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

    /**
     * @throws Exception
     */
    #[Test]
    public function can_clean_cdata(): void {
        $testString = '<![CDATA[This is some content]]>';
        $expected   = 'This is some content';

        $this->assertEquals( $expected, Strings::cleanCDATA( $testString ) );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function can_clean_cdata_with_special_chars(): void {
        $testString = '<![CDATA[Text with <tags> and & special chars]]>';
        $expected   = 'Text with <tags> and & special chars';

        $this->assertEquals( $expected, Strings::cleanCDATA( $testString ) );
    }

    #[Test]
    public function cleanCDATA_throws_exception_on_invalid_xml(): void {
        $this->expectException( Exception::class );
        Strings::cleanCDATA( '<invalid><xml' );
    }

    #[Test]
    public function can_remove_dangerous_chars(): void {
        // Test with control characters (ASCII < 32, excluding 0A, 0D, 09)
        $string = "Hello\x00\x01\x02World";
        $result = Strings::removeDangerousChars( $string );
        $this->assertEquals( "HelloWorld", $result );

        // Test with tab, newline, carriage return (should be kept)
        $string = "Hello\t\n\rWorld";
        $result = Strings::removeDangerousChars( $string );
        $this->assertEquals( "Hello\t\n\rWorld", $result );

        // Test with DEL character (0x7F)
        $string = "Hello\x7FWorld";
        $result = Strings::removeDangerousChars( $string );
        $this->assertEquals( "HelloWorld", $result );
    }

    #[Test]
    public function can_remove_dangerous_xml_entities(): void {
        // Test with invalid XML entities
        $string = "Hello&#x00;&#x01;&#x08;World";
        $result = Strings::removeDangerousChars( $string );
        $this->assertEquals( "HelloWorld", $result );

        // Test with more invalid entities
        $string = "Text&#x0B;&#x0C;&#x0E;&#x1F;&#x7F;End";
        $result = Strings::removeDangerousChars( $string );
        $this->assertEquals( "TextEnd", $result );

        // Valid entities should be kept
        $string = "Hello&#x20;&#x21;World";
        $result = Strings::removeDangerousChars( $string );
        $this->assertEquals( "Hello&#x20;&#x21;World", $result );
    }

    #[Test]
    public function removeDangerousChars_handles_null_input(): void {
        $result = Strings::removeDangerousChars( null );
        $this->assertEquals( "", $result );
    }

    #[Test]
    public function removeDangerousChars_handles_empty_string(): void {
        $result = Strings::removeDangerousChars( "" );
        $this->assertEquals( "", $result );
    }

    #[Test]
    public function can_decode_all_html_entities(): void {
        $string   = "&lt;p&gt;Hello &amp; goodbye&lt;/p&gt;";
        $expected = "<p>Hello & goodbye</p>";

        $this->assertEquals( $expected, Strings::htmlspecialchars_decode( $string) );
    }

    #[Test]
    public function can_decode_all_html_entities_default_parameter(): void {
        $string   = "&lt;p&gt;Hello &amp; goodbye&lt;/p&gt;";
        $expected = "<p>Hello & goodbye</p>";

        // Test with default parameter (false)
        $this->assertEquals( $expected, Strings::htmlspecialchars_decode( $string ) );
    }

    #[Test]
    public function htmlspecialchars_decode_handles_mixed_entities(): void {
        $string   = "&lt;p&gt; &amp;#39; &amp;amp; &quot;";
        $expected = "&lt;p&gt; &#39; &amp; &quot;";

        $this->assertEquals( $expected, Strings::htmlspecialchars_decode( $string, true ) );
    }

    #[Test]
    public function can_use_preg_split(): void {
        $pattern = '/\s+/';
        $subject = "Hello   world  this   is   a   test";
        $result  = Strings::preg_split( $pattern, $subject );

        $expected = [ 'Hello', 'world', 'this', 'is', 'a', 'test' ];
        $this->assertEquals( $expected, $result );
    }

    #[Test]
    public function preg_split_removes_empty_strings(): void {
        $pattern = '/,/';
        $subject = "one,,two,,,three";
        $result  = Strings::preg_split( $pattern, $subject );

        $expected = [ 'one', 'two', 'three' ];
        $this->assertEquals( $expected, $result );
    }

    #[Test]
    public function preg_split_handles_no_matches(): void {
        $pattern = '/,/';
        $subject = "no-commas-here";
        $result  = Strings::preg_split( $pattern, $subject );

        $expected = [ 'no-commas-here' ];
        $this->assertEquals( $expected, $result );
    }

    #[Test]
    public function isJSON_handles_empty_string(): void {
        $this->assertFalse( Strings::isJSON( '' ) );
    }

    #[Test]
    public function isJSON_handles_whitespace_only(): void {
        $this->assertFalse( Strings::isJSON( '   ' ) );
    }

    #[Test]
    public function isJSON_handles_numeric_string(): void {
        $this->assertFalse( Strings::isJSON( '123' ) );
        $this->assertFalse( Strings::isJSON( '123.45' ) );
    }

    #[Test]
    public function isJSON_handles_invalid_json_with_exception(): void {
        // Invalid XML that causes exception in cleanCDATA
        $this->assertFalse( Strings::isJSON( '<invalid><xml' ) );
    }

    #[Test]
    public function isJSON_handles_valid_nested_json(): void {
        $json = '{"key": {"nested": {"deep": "value"}}}';
        $this->assertTrue( Strings::isJSON( $json ) );
    }

    #[Test]
    public function isJSON_handles_array_with_objects(): void {
        $json = '[{"key": "value"}, {"key2": "value2"}]';
        $this->assertTrue( Strings::isJSON( $json ) );
    }

    #[Test]
    public function isJSON_handles_malformed_json(): void {
        $this->assertFalse( Strings::isJSON( '{"key": value}' ) ); // missing quotes
        $this->assertFalse( Strings::isJSON( '{key: "value"}' ) ); // missing quotes on key
        $this->assertFalse( Strings::isJSON( '{"key": "value",}' ) ); // trailing comma
    }

    #[Test]
    public function jsonToArray_returns_empty_array_for_invalid_json(): void {
        $this->assertEmpty( Strings::jsonToArray( 'invalid json' ) );
        $this->assertEmpty( Strings::jsonToArray( '{invalid}' ) );
    }

    #[Test]
    public function jsonToArray_handles_nested_arrays(): void {
        $json = '{"key": ["value1", "value2"], "key2": {"nested": "value"}}';
        $result = Strings::jsonToArray( $json );

        $this->assertArrayHasKey( 'key', $result );
        $this->assertIsArray( $result['key'] );
        $this->assertEquals( 'value1', $result['key'][0] );
    }

    #[Test]
    public function fixNonWellFormedXml_handles_no_escaping(): void {
        $original = '<g id="1">Hello</g> World';
        $expected = '<g id="1">Hello</g> World';

        $result = Strings::fixNonWellFormedXml( $original, false );
        $this->assertEquals( $expected, $result );
    }

    #[Test]
    public function fixNonWellFormedXml_handles_complex_xliff_tags(): void {
        $original = '<mrk mtype="seg">Text</mrk> & <bpt id="1">Start</bpt> content <ept id="1">End</ept>';
        $expected = '<mrk mtype="seg">Text</mrk> &amp; <bpt id="1">Start</bpt> content <ept id="1">End</ept>';

        $result = Strings::fixNonWellFormedXml( $original );
        $this->assertEquals( $expected, $result );
    }

    #[Test]
    public function fixNonWellFormedXml_handles_self_closing_tags(): void {
        $original = '<ph id="1"/> text <x id="2"/> more';
        $expected = '<ph id="1"/> text <x id="2"/> more';

        $result = Strings::fixNonWellFormedXml( $original );
        $this->assertEquals( $expected, $result );
    }

    #[Test]
    public function fixNonWellFormedXml_handles_quotes_in_attributes(): void {
        $original = '<g id="1" attr="value with &quot;quotes&quot;">Text</g>';
        $expected = '<g id="1" attr="value with &quot;quotes&quot;">Text</g>';

        $result = Strings::fixNonWellFormedXml( $original );
        $this->assertEquals( $expected, $result );
    }

    #[Test]
    public function isAValidUuidV4_handles_edge_cases(): void {
        // Valid UUIDs
        $this->assertTrue( Strings::isAValidUuidV4( '550e8400-e29b-41d4-a716-446655440000' ) );
        $this->assertTrue( Strings::isAValidUuidV4( 'f47ac10b-58cc-4372-a567-0e02b2c3d479' ) );

        // Invalid UUIDs - wrong version
        $this->assertFalse( Strings::isAValidUuidV4( '550e8400-e29b-31d4-a716-446655440000' ) ); // version 3

        // Invalid UUIDs - wrong variant
        $this->assertFalse( Strings::isAValidUuidV4( '550e8400-e29b-41d4-c716-446655440000' ) ); // wrong variant

        // Invalid UUIDs - wrong format
        $this->assertFalse( Strings::isAValidUuidV4( '550e8400-e29b-41d4-a716' ) ); // too short
        $this->assertFalse( Strings::isAValidUuidV4( '550e8400-e29b-41d4-a716-446655440000-extra' ) ); // too long
        $this->assertFalse( Strings::isAValidUuidV4( '' ) ); // empty
    }

    #[Test]
    public function isADoubleEscapedEntity_handles_various_entities(): void {
        // Double escaped entities
        $this->assertTrue( Strings::isADoubleEscapedEntity( '&amp;#39;' ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( '&amp;amp;' ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( '&amp;apos;' ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( '&amp;quot;' ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( '&amp;lt;' ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( '&amp;gt;' ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( '&amp;nbsp;' ) );
        $this->assertTrue( Strings::isADoubleEscapedEntity( '&amp;#123;' ) );

        // Single escaped entities
        $this->assertFalse( Strings::isADoubleEscapedEntity( '&#39;' ) );
        $this->assertFalse( Strings::isADoubleEscapedEntity( '&amp;' ) );
        $this->assertFalse( Strings::isADoubleEscapedEntity( '&lt;' ) );
        $this->assertFalse( Strings::isADoubleEscapedEntity( '&gt;' ) );
        $this->assertFalse( Strings::isADoubleEscapedEntity( '&quot;' ) );

        // Regular text
        $this->assertFalse( Strings::isADoubleEscapedEntity( 'regular text' ) );
        $this->assertFalse( Strings::isADoubleEscapedEntity( '&' ) );
    }

    #[Test]
    public function getTheNumberOfTrailingSpaces_handles_edge_cases(): void {
        // Empty string
        $this->assertEquals( 0, Strings::getTheNumberOfTrailingSpaces( '' ) );

        // Only spaces
        $this->assertEquals( 5, Strings::getTheNumberOfTrailingSpaces( '     ' ) );

        // No trailing spaces
        $this->assertEquals( 0, Strings::getTheNumberOfTrailingSpaces( 'no trailing spaces' ) );

        // Leading spaces only
        $this->assertEquals( 0, Strings::getTheNumberOfTrailingSpaces( '     leading only' ) );

        // Both leading and trailing
        $this->assertEquals( 3, Strings::getTheNumberOfTrailingSpaces( '   both   ' ) );
    }
}
