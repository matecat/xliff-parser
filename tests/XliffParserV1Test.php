<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffParserV1Test extends BaseTest
{
    /**
     * @test
     */
    public function parses_with_no_errors()
    {
        // read a file with notes inside
        $parsed = XliffParser::toArray($this->getTestFile('file-with-notes-converted-nobase64.xliff'));

        $this->assertEquals('Input identified as ASCII ans converted UTF-8. May not be a problem if the content is English only', $parsed['parser-warnings'][0]);
        $this->assertNotEmpty($parsed['files']);
        $this->assertCount(2, $parsed['files']);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v1_metadata()
    {
        $parsed = XliffParser::toArray($this->getTestFile('file-with-notes-converted-nobase64.xliff'));
        $attr   = $parsed[ 'files' ][ 2 ][ 'attr' ];

        $this->assertCount(5, $attr);
        $this->assertEquals($attr[ 'source-language' ], 'hy-am');
        $this->assertEquals($attr[ 'target-language' ], 'fr-fr');
        $this->assertEquals($attr[ 'original' ], '');
        $this->assertEquals($attr[ 'data-type' ], 'x-plaintext');
        $this->assertEquals($attr[ 'custom' ]['x-data'], 'ciao');
        $this->assertEquals($attr[ 'custom' ]['x-matecat'], 'matecat');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v1_reference()
    {
        $parsed = XliffParser::toArray($this->getTestFile('file-with-notes-converted-nobase64.xliff'));
        $reference   = $parsed[ 'files' ][ 1 ][ 'reference' ];

        $this->assertCount(2, $reference[0]);
        $this->assertEquals($reference[0][ 'form-type' ], 'base64');
        $this->assertEquals($reference[0][ 'base64' ], 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4NCjwhLS09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PS0tPg0KPCEtLVBMRUFTRSwgRE8gTk9UIFJFTkFNRSwgTU9WRSwgTU9ESUZZIE9SIEFMVEVSIElOIEFOWSBXQVkgVEhJUyBGSUxFLS0+DQo8IS0tPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0tLT4NCjxtYW5pZmVzdCB2ZXJzaW9uPSIyIiBsaWJWZXJzaW9uPSIiIHByb2plY3RJZD0iTkM1QzkzQURFIiBwYWNrYWdlSWQ9IjY4ODc2NjIyLTQzZWItNDdiYy1hY2VmLWFmNjNlNWQwOTE5OSIgc291cmNlPSJoeS1hbSIgdGFyZ2V0PSJmci1mciIgb3JpZ2luYWxTdWJEaXI9Im9yaWdpbmFsIiBza2VsZXRvblN1YkRpcj0ic2tlbGV0b24iIHNvdXJjZVN1YkRpcj0id29yayIgdGFyZ2V0U3ViRGlyPSJ3b3JrIiBtZXJnZVN1YkRpcj0iZG9uZSIgdG1TdWJEaXI9IiIgZGF0ZT0iMjAxNS0xMC0wNiAxNjo1ODowMCswMDAwIiB1c2VBcHByb3ZlZE9ubHk9IjAiIHVwZGF0ZUFwcHJvdmVkRmxhZz0iMCI+DQo8Y3JlYXRvclBhcmFtZXRlcnM+PC9jcmVhdG9yUGFyYW1ldGVycz4NCjxkb2MgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgZG9jSWQ9IjEiIGV4dHJhY3Rpb25UeXBlPSJ4bGlmZiIgcmVsYXRpdmVJbnB1dFBhdGg9IkViYXktbGlrZS1zbWFsbC1maWxlLWVkaXRlZC54bGYiIGZpbHRlcklkPSJva2ZfeGxpZmYiIGlucHV0RW5jb2Rpbmc9InV0Zi04IiByZWxhdGl2ZVRhcmdldFBhdGg9IkViYXktbGlrZS1zbWFsbC1maWxlLWVkaXRlZC5vdXQueGxmIiB0YXJnZXRFbmNvZGluZz0iVVRGLTgiIHNlbGVjdGVkPSIxIj5JM1l4Q25WelpVTjFjM1J2YlZCaGNuTmxjaTVpUFhSeWRXVUtabUZqZEc5eWVVTnNZWE56UFdOdmJTNWpkR011ZDNOMGVDNXpkR0Y0TGxkemRIaEpibkIxZEVaaFkzUnZjbmtLWm1Gc2JHSmhZMnRVYjBsRUxtSTlabUZzYzJVS1pYTmpZWEJsUjFRdVlqMW1ZV3h6WlFwaFpHUlVZWEpuWlhSTVlXNW5kV0ZuWlM1aVBYUnlkV1VLYjNabGNuSnBaR1ZVWVhKblpYUk1ZVzVuZFdGblpTNWlQV1poYkhObENtOTFkSEIxZEZObFoyMWxiblJoZEdsdmJsUjVjR1V1YVQwekNtbG5ibTl5WlVsdWNIVjBVMlZuYldWdWRHRjBhVzl1TG1JOVptRnNjMlVLWVdSa1FXeDBWSEpoYm5NdVlqMW1ZV3h6WlFwaFpHUkJiSFJVY21GdWMwZE5iMlJsTG1JOWRISjFaUXBsWkdsMFFXeDBWSEpoYm5NdVlqMW1ZV3h6WlFwcGJtTnNkV1JsUlhoMFpXNXphVzl1Y3k1aVBYUnlkV1VLYVc1amJIVmtaVWwwY3k1aVBYUnlkV1VLWW1Gc1lXNWpaVU52WkdWekxtSTlkSEoxWlFwaGJHeHZkMFZ0Y0hSNVZHRnlaMlYwY3k1aVBXWmhiSE5sQ25SaGNtZGxkRk4wWVhSbFRXOWtaUzVwUFRBS2RHRnlaMlYwVTNSaGRHVldZV3gxWlQxdVpXVmtjeTEwY21GdWMyeGhkR2x2YmdwaGJIZGhlWE5WYzJWVFpXZFRiM1Z5WTJVdVlqMW1ZV3h6WlFweGRXOTBaVTF2WkdWRVpXWnBibVZrTG1JOWRISjFaUXB4ZFc5MFpVMXZaR1V1YVQwd0NuVnpaVk5rYkZoc2FXWm1WM0pwZEdWeUxtSTlabUZzYzJVPTwvZG9jPg0KPC9tYW5pZmVzdD4=');
    }

//    /**
//     * @test
//     */
//    function testParsesNoteElements() {
//        // read a file with notes inside
//        $parsed = XliffParser::toArray($this->getTestFile('file-with-notes-nobase64.po.sdlxliff'));
//
//        $this->assertEquals( 'This is a comment',
//                $parsed['files'][1]['trans-units'][4]['notes'][0]['raw-content']);
//
//        $this->assertEquals( 'This is another comment',
//                $parsed['files'][1]['trans-units'][6]['notes'][0]['raw-content']);
//    }

//    function testConvertedFile() {
//        $file = test_file_path('xliff/file-with-notes-converted.xliff');
//        $content = file_get_contents( $file );
//
//        $xliff_obj = new Xliff_Parser();
//        $xliff     = $xliff_obj->Xliff2Array( $content );
//
//        $this->assertEquals(
//                "This is a comment\n" .
//                "---\n" .
//                "This is a comment number two\n" .
//                "---\n" .
//                "This is a comment number three",
//
//                $xliff['files'][3]['trans-units'][1]['notes'][0]['raw-content']
//        );
//
//        $this->assertEquals( 'This is another comment',
//                $xliff['files'][3]['trans-units'][3]['notes'][0]['raw-content']);
//
//    }
//
//    function testFileWithMaliciousNote() {
//        $file = test_file_path('xliff/file-with-notes-and-malicious-code.xliff');
//        $content = file_get_contents( $file );
//
//        $xliff_obj = new Xliff_Parser();
//        $xliff     = $xliff_obj->Xliff2Array( $content );
//
//        $this->assertEquals(
//                "&lt;script&gt;alert('This is malicious code');&lt;/script&gt;",
//                $xliff['files'][3]['trans-units'][1]['notes'][0]['raw-content']
//        );
//
//    }
//
//    public function testFixNotWellFormedXML(){
//
//        $tests = array(
//                '' => '',
//                'just text' => 'just text',
//                '<gap>Hey</gap>' => '&lt;gap&gt;Hey&lt;/gap&gt;',
//                '<mrk>Hey</mrk>' => '<mrk>Hey</mrk>',
//                '<g >Hey</g >' => '<g >Hey</g >',
//                '<g    >Hey</g   >' => '<g    >Hey</g   >',
//                '<g id="99">Hey</g>' => '<g id="99">Hey</g>',
//                'Hey<x/>' => 'Hey<x/>',
//                'Hey<x />' => 'Hey<x />',
//                'Hey<x   />' => 'Hey<x   />',
//                'Hey<x id="15"/>' => 'Hey<x id="15"/>',
//                'Hey<bx id="1"/>' => 'Hey<bx id="1"/>',
//                'Hey<ex id="1"/>' => 'Hey<ex id="1"/>',
//                '<bpt id="1">Hey</bpt>' => '<bpt id="1">Hey</bpt>',
//                '<ept id="1">Hey</ept>' => '<ept id="1">Hey</ept>',
//                '<ph id="1">Hey</ph>' => '<ph id="1">Hey</ph>',
//                '<it id="1">Hey</it>' => '<it id="1">Hey</it>',
//                '<mrk mid="3" mtype="seg"><g id="2">Hey man! <x id="1"/><b id="dunno">Hey man & hey girl!</b></mrk>' => '<mrk mid="3" mtype="seg"><g id="2">Hey man! <x id="1"/>&lt;b id="dunno"&gt;Hey man &amp; hey girl!&lt;/b&gt;</mrk>',
//        );
//
//        foreach ($tests as $in => $expected) {
//            $out = Xliff_Parser::fix_non_well_formed_xml($in);
//            $this->assertEquals( $expected, $out );
//        }
//
//    }
//
//    public function testEmptySelfClosedTargetTagWithAltTrans(){
//
//        $x = "<trans-unit id=\"0000000121\" datatype=\"x-text/x-4cb\" restype=\"string\">
//    <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//    <target/>
//    <alt-trans match-quality=\"100.00\" origin=\"Sparta CAT\">
//        <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//        <target>Hemos disminuido el importe mensual procedente de las ventas del que puede disponer inmediatamente</target>
//    </alt-trans>
//</trans-unit>
//";
//
//        $refMethod = new ReflectionMethod( 'Xliff_Parser', 'getTarget' );
//        $refMethod->setAccessible(true);
//
//        $xliff = [];
//        $refMethod->invokeArgs( new Xliff_Parser(), [ &$xliff, 0, 0, $x ] );
//        $this->assertEmpty( $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'target' ][ 'raw-content' ] );
//    }
//
//    public function testEmojiInSource(){
//
//        $x = "<trans-unit id=\"NFDBB2FA9-tu519\" xml:space=\"preserve\">
//<source xml:lang=\"ru-RU\"><g id=\"1\">&#128076;&#127995;</g></source>
//<seg-source><mrk mid=\"0\" mtype=\"seg\"><g id=\"1\">&#128076;&#127995;</g></mrk></seg-source>
//<target xml:lang=\"sk-SK\"></target>
//<group mtc:name=\"x-matecat-word-count\"><count-group name=\"NFDBB2FA9-tu519\"><count count-type=\"x-matecat-raw\">1</count><count count-type=\"x-matecat-weighted\">0</count></count-group></group>
//</trans-unit>
//";
//
//        $refMethod = new ReflectionMethod( 'Xliff_Parser', 'getSource' );
//        $refMethod->setAccessible(true);
//
//        $xliff = [];
//        $refMethod->invokeArgs( new Xliff_Parser(), [ &$xliff, 0, 0, $x ] );
//
//        $this->assertNotEmpty( $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'source' ][ 'raw-content' ] );
//
//        //the emoticons are not displayed in the IDE but they are present
//        $this->assertEquals(  '<g id="1">👌🏻</g>', $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'source' ][ 'raw-content' ] );
//    }
//
//    public function testEmptyNotSelfClosedTargetTagWithAltTrans(){
//
//        $x = "<trans-unit id=\"0000000121\" datatype=\"x-text/x-4cb\" restype=\"string\">
//    <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//    <target></target>
//    <alt-trans match-quality=\"100.00\" origin=\"Sparta CAT\">
//        <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//        <target>Hemos disminuido el importe mensual procedente de las ventas del que puede disponer inmediatamente</target>
//    </alt-trans>
//</trans-unit>
//";
//
//        $refMethod = new ReflectionMethod( 'Xliff_Parser', 'getTarget' );
//        $refMethod->setAccessible(true);
//
//        $xliff = [];
//        $refMethod->invokeArgs( new Xliff_Parser(), [ &$xliff, 0, 0, $x ] );
//
//        $this->assertEmpty( $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'target' ][ 'raw-content' ] );
//
//
//    }
//
//    public function testNotEmptyTargetTagWithNotOrderedAltTrans(){
//
//        $x = "<trans-unit id=\"0000000002\" datatype=\"x-text/x-4cb\" restype=\"string\">
//    <alt-trans match-quality=\"100.00\" origin=\"Sparta CAT\">
//        <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//        <target>Hemos disminuido el importe mensual procedente de las ventas del que puede disponer inmediatamente</target>
//    </alt-trans>
//    <source>PPC000460</source>
//    <target>PPC000460</target>
//</trans-unit>";
//
//        $refMethod = new ReflectionMethod( 'Xliff_Parser', 'getTarget' );
//        $refMethod->setAccessible(true);
//
//        $xliff = [];
//        $refMethod->invokeArgs( new Xliff_Parser(), [ &$xliff, 0, 0, $x ] );
//
//        $this->assertNotEmpty( $xliff );
//        $this->assertEquals( "PPC000460", $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'target' ][ 'raw-content' ] );
//
//
//    }
//
//    public function testNotEmptyTargetTagWithoutAltTrans(){
//
//        $x = "<trans-unit id=\"0000000002\" datatype=\"x-text/x-4cb\" restype=\"string\">
//    <source>PPC000460</source>
//    <target>PPC000460</target>
//</trans-unit>";
//
//        $refMethod = new ReflectionMethod( 'Xliff_Parser', 'getTarget' );
//        $refMethod->setAccessible(true);
//
//        $xliff = [];
//        $refMethod->invokeArgs( new Xliff_Parser(), [ &$xliff, 0, 0, $x ] );
//
//        $this->assertNotEmpty( $xliff );
//        $this->assertEquals( "PPC000460", $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'target' ][ 'raw-content' ] );
//
//
//
//    }
//
//    public function testNotEmptyTargetTagWithMrkWithAltTrans(){
//
//        $x = "<trans-unit id=\"0000000002\" datatype=\"x-text/x-4cb\" restype=\"string\">
//    <source><mrk id=\"1\">PPC000460</mrk></source>
//    <target><mrk id=\"1\">PPC000460</mrk></target>
//    <alt-trans match-quality=\"100.00\" origin=\"Sparta CAT\">
//        <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//        <target>Hemos disminuido el importe mensual procedente de las ventas del que puede disponer inmediatamente</target>
//    </alt-trans>
//</trans-unit>";
//
//        $refMethod = new ReflectionMethod( 'Xliff_Parser', 'getTarget' );
//        $refMethod->setAccessible(true);
//
//        $xliff = [];
//        $refMethod->invokeArgs( new Xliff_Parser(), [ &$xliff, 0, 0, $x ] );
//
//        $this->assertNotEmpty( $xliff );
//        $this->assertEquals( "<mrk id=\"1\">PPC000460</mrk>", $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'target' ][ 'raw-content' ] );
//
//
//    }
//
//    public function testNotEmptyTargetTagWithSomeMrkWithAltTrans(){
//
//        $x = "<trans-unit id=\"0000000002\" datatype=\"x-text/x-4cb\" restype=\"string\">
//    <alt-trans match-quality=\"100.00\" origin=\"Sparta CAT\">
//        <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//        <target>Hemos disminuido el importe mensual procedente de las ventas del que puede disponer inmediatamente</target>
//    </alt-trans>
//    <source><mrk id=\"1\">PPC000460</mrk></source>
//    <target><mrk id=\"1\">Test1</mrk><mrk id=\"2\">Test2</mrk><mrk id=\"3\">Test3</mrk></target>
//    <alt-trans match-quality=\"100.00\" origin=\"Sparta CAT\">
//        <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//        <target>Hemos disminuido el importe mensual procedente de las ventas del que puede disponer inmediatamente</target>
//    </alt-trans>
//</trans-unit>";
//
//        $refMethod = new ReflectionMethod( 'Xliff_Parser', 'getTarget' );
//        $refMethod->setAccessible(true);
//
//        $xliff = [];
//        $refMethod->invokeArgs( new Xliff_Parser(), [ &$xliff, 0, 0, $x ] );
//
//        $this->assertNotEmpty( $xliff );
//        $this->assertEquals( "<mrk id=\"1\">Test1</mrk><mrk id=\"2\">Test2</mrk><mrk id=\"3\">Test3</mrk>", $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'target' ][ 'raw-content' ] );
//
//    }
//
//    public function testNotEmptyTargetTagWithSomeMrkAndHtmlWithAltTrans(){
//
//        $x = "<trans-unit id=\"0000000002\" datatype=\"x-text/x-4cb\" restype=\"string\">
//    <alt-trans match-quality=\"100.00\" origin=\"Sparta CAT\">
//        <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//        <target>Hemos disminuido el importe mensual procedente de las ventas del que puede disponer inmediatamente</target>
//    </alt-trans>
//    <source><mrk id=\"1\">PPC000460</mrk></source>
//    <target><mrk id=\"1\">Test1</mrk><mrk id=\"2\">Test2<ex id=\"1\">Another Test Inside</ex></mrk><mrk id=\"3\">Test3<a href=\"https://example.org\">ClickMe!</a></mrk></target>
//    <alt-trans match-quality=\"100.00\" origin=\"Sparta CAT\">
//        <source>We’ve decreased the amount of money from sales immediately available to you each month</source>
//        <target>Hemos disminuido el importe mensual procedente de las ventas del que puede disponer inmediatamente</target>
//    </alt-trans>
//</trans-unit>";
//
//        $refMethod = new ReflectionMethod( 'Xliff_Parser', 'getTarget' );
//        $refMethod->setAccessible(true);
//
//        $xliff = [];
//        $refMethod->invokeArgs( new Xliff_Parser(), [ &$xliff, 0, 0, $x ] );
//
//        $this->assertNotEmpty( $xliff );
//        $this->assertEquals(
//                "<mrk id=\"1\">Test1</mrk><mrk id=\"2\">Test2<ex id=\"1\">Another Test Inside</ex></mrk><mrk id=\"3\">Test3&lt;a href=\"https://example.org\"&gt;ClickMe!&lt;/a&gt;</mrk>",
//                $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'target' ][ 'raw-content' ]
//        );
//
//    }
//
//    public function testComplexStructure(){
//
//        $x = "<trans-unit id=\"0000000035\" datatype=\"x-text/x-4cb\" restype=\"string\">
//    <source>
//        <ph id=\"59\" x=\"&lt;endcmp/>\">{59}</ph>
//        <ph id=\"60\" x=\"&lt;/span>\">{60}</ph>
//        <ph id=\"61\" x=\"&lt;startcmp/>\">{61}</ph>
//        <ph id=\"62\" x=\"&lt;endcmp/>\">{62}</ph>
//        <ph id=\"63\" x=\"&lt;startcmp/>\">{63}</ph>
//        <ph id=\"64\" x=\"&lt;endcmp/>\">{64}</ph>
//        <ph id=\"65\" x=\"&lt;startcmp/>\">{65}</ph>
//        <ph id=\"66\" x=\"&lt;endcmp/>\">{66}</ph>
//        <ph id=\"67\" x=\"&lt;span class=&quot;listContentDPHContentBlockSpecificValue ecat-block aloha-block aloha-block-ListContentDPHContentBlockSpecificValue&quot; contenteditable=&quot;false&quot; data-aloha-block-type=&quot;ListContentDPHContentBlockSpecificValue&quot; data-listcontentdphfield=&quot;test.DecreaseReleaseAmount&quot; data-listcontentdphspecificvalue=&quot;default&quot; id=&quot;9705d4c0-824b-0c49-e631-91c34666bc9f&quot; style=&quot;display:block;border: 1px #90f dashed;&quot;>\">
//            {67}
//        </ph>
//        <ph id=\"68\" x=\"&lt;span class=&quot;specificContentDiv handleContainer&quot; style=&quot;word-break:break-all;display:block; background-color:rgb(265, 275, 166);&quot;>\">{68}</ph>
//        <ph id=\"69\" x=\"&lt;span class=&quot;specificContentDiv editHandle&quot; contenteditable=&quot;false&quot; style=&quot;word-break:break-all;display:block;background-color:rgb(265, 275, 166);&quot;>\">
//            {69}
//        </ph>
//        <ph id=\"70\" x=\"&lt;strong>\">{70}</ph>Choice Content - default
//        <ph id=\"71\" x=\"&lt;/strong>\">{71}</ph>
//        <ph id=\"72\" x=\"&lt;/span>\">{72}</ph>
//        <ph id=\"73\" x=\"&lt;/span>\">{73}</ph>
//        <ph id=\"74\" x=\"&lt;startcmp/>\">{74}</ph>
//    </source>
//    <target>
//        <ph id=\"59\" x=\"&lt;endcmp/>\">{59}</ph>
//        <ph id=\"60\" x=\"&lt;/span>\">{60}</ph>
//        <ph id=\"61\" x=\"&lt;startcmp/>\">{61}</ph>
//        <ph id=\"62\" x=\"&lt;endcmp/>\">{62}</ph>
//        <ph id=\"63\" x=\"&lt;startcmp/>\">{63}</ph>
//        <ph id=\"64\" x=\"&lt;endcmp/>\">{64}</ph>
//        <ph id=\"65\" x=\"&lt;startcmp/>\">{65}</ph>
//        <ph id=\"66\" x=\"&lt;endcmp/>\">{66}</ph>
//        <ph id=\"67\" x=\"&lt;span class=&quot;listContentDPHContentBlockSpecificValue ecat-block aloha-block aloha-block-ListContentDPHContentBlockSpecificValue&quot; contenteditable=&quot;false&quot; data-aloha-block-type=&quot;ListContentDPHContentBlockSpecificValue&quot; data-listcontentdphfield=&quot;test.DecreaseReleaseAmount&quot; data-listcontentdphspecificvalue=&quot;default&quot; id=&quot;9705d4c0-824b-0c49-e631-91c34666bc9f&quot; style=&quot;display:block;border: 1px #90f dashed;&quot;>\">
//            {67}
//        </ph>
//        <ph id=\"68\" x=\"&lt;span class=&quot;specificContentDiv handleContainer&quot; style=&quot;word-break:break-all;display:block; background-color:rgb(265, 275, 166);&quot;>\">{68}</ph>
//        <ph id=\"69\" x=\"&lt;span class=&quot;specificContentDiv editHandle&quot; contenteditable=&quot;false&quot; style=&quot;word-break:break-all;display:block;background-color:rgb(265, 275, 166);&quot;>\">
//            {69}
//        </ph>
//        <ph id=\"70\" x=\"&lt;strong>\">{70}</ph>
//        <ph id=\"71\" x=\"&lt;/strong>\">{71}</ph>
//        <ph id=\"72\" x=\"&lt;/span>\">{72}</ph>
//        <ph id=\"73\" x=\"&lt;/span>\">{73}</ph>
//        <ph id=\"74\" x=\"&lt;startcmp/>\">{74}</ph>
//    </target>
//    <alt-trans match-quality=\"100.00\" origin=\"Sparta CAT\">
//        <source>
//            <ph id=\"1\">{1}</ph>
//            <ph id=\"2\">{2}</ph>
//            <ph id=\"3\">{3}</ph>
//            <ph id=\"4\">{4}</ph>
//            <ph id=\"5\">{5}</ph>
//            <ph id=\"6\">{6}</ph>
//            <ph id=\"7\">{7}</ph>
//            <ph id=\"8\">{8}</ph>
//            <ph id=\"9\">{9}</ph>
//            <ph id=\"10\">{10}</ph>
//            <ph id=\"11\">{11}</ph>
//            <ph id=\"12\">{12}</ph>Choice Content - default
//            <ph id=\"13\">{13}</ph>
//            <ph id=\"14\">{14}</ph>
//            <ph id=\"15\">{15}</ph>
//            <ph id=\"16\">{16}</ph>
//        </source>
//        <target>
//            <ph id=\"1\">{1}</ph>
//            <ph id=\"2\">{2}</ph>
//            <ph id=\"3\">{3}</ph>
//            <ph id=\"4\">{4}</ph>
//            <ph id=\"5\">{5}</ph>
//            <ph id=\"6\">{6}</ph>
//            <ph id=\"7\">{7}</ph>
//            <ph id=\"8\">{8}</ph>
//            <ph id=\"9\">{9}</ph>
//            <ph id=\"10\">{10}</ph>
//            <ph id=\"11\">{11}</ph>
//            <ph id=\"12\">{12}</ph>Choice Content - default
//            <ph id=\"13\">{13}</ph>
//            <ph id=\"14\">{14}</ph>
//            <ph id=\"15\">{15}</ph>
//            <ph id=\"16\">{16}</ph>
//        </target>
//        <iws:tm_entry_id tm_entry_id_value=\"21401420\"/>
//        <iws:is-reverse-leveraged reverse=\"false\"/>
//        <iws:is-repaired-match repaired=\"false\"/>
//        <iws:status translation_status=\"finished\"/>
//        <iws:asset-origin origin=\"/source/en_ES/content/msgrenderingapp/1.0.0/PPC000460/perm13.4cb\"/>
//        <iws:attribute name=\"_tm_created_by\">Marta Chico</iws:attribute>
//        <iws:attribute name=\"_tm_created_date\">1503056686433</iws:attribute>
//        <iws:attribute name=\"_tm_modified_by\">Marta Chico</iws:attribute>
//        <iws:attribute name=\"_tm_modified_date\">1503056686433</iws:attribute>
//        <iws:attribute name=\"_tm_sid\">msgrenderingapp/Transactions/Holds/PPC000460/perm13/en_AD/messageSubject/d41d8cd98f00b204e9800998ecf8427e</iws:attribute>
//    </alt-trans>
//    </trans-unit>";
//
//
//        $refMethod = new ReflectionMethod( 'Xliff_Parser', 'getTarget' );
//        $refMethod->setAccessible(true);
//
//        $xliff = [];
//        $refMethod->invokeArgs( new Xliff_Parser(), [ &$xliff, 0, 0, $x ] );
//
//        $this->assertNotEmpty( $xliff );
//
//        $this->assertEquals(
//                '
//        <ph id="59" x="&lt;endcmp/&gt;">{59}</ph>
//        <ph id="60" x="&lt;/span&gt;">{60}</ph>
//        <ph id="61" x="&lt;startcmp/&gt;">{61}</ph>
//        <ph id="62" x="&lt;endcmp/&gt;">{62}</ph>
//        <ph id="63" x="&lt;startcmp/&gt;">{63}</ph>
//        <ph id="64" x="&lt;endcmp/&gt;">{64}</ph>
//        <ph id="65" x="&lt;startcmp/&gt;">{65}</ph>
//        <ph id="66" x="&lt;endcmp/&gt;">{66}</ph>
//        <ph id="67" x="&lt;span class=&quot;listContentDPHContentBlockSpecificValue ecat-block aloha-block aloha-block-ListContentDPHContentBlockSpecificValue&quot; contenteditable=&quot;false&quot; data-aloha-block-type=&quot;ListContentDPHContentBlockSpecificValue&quot; data-listcontentdphfield=&quot;test.DecreaseReleaseAmount&quot; data-listcontentdphspecificvalue=&quot;default&quot; id=&quot;9705d4c0-824b-0c49-e631-91c34666bc9f&quot; style=&quot;display:block;border: 1px #90f dashed;&quot;&gt;">
//            {67}
//        </ph>
//        <ph id="68" x="&lt;span class=&quot;specificContentDiv handleContainer&quot; style=&quot;word-break:break-all;display:block; background-color:rgb(265, 275, 166);&quot;&gt;">{68}</ph>
//        <ph id="69" x="&lt;span class=&quot;specificContentDiv editHandle&quot; contenteditable=&quot;false&quot; style=&quot;word-break:break-all;display:block;background-color:rgb(265, 275, 166);&quot;&gt;">
//            {69}
//        </ph>
//        <ph id="70" x="&lt;strong&gt;">{70}</ph>
//        <ph id="71" x="&lt;/strong&gt;">{71}</ph>
//        <ph id="72" x="&lt;/span&gt;">{72}</ph>
//        <ph id="73" x="&lt;/span&gt;">{73}</ph>
//        <ph id="74" x="&lt;startcmp/&gt;">{74}</ph>
//    ',
//                $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'target' ][ 'raw-content' ]
//        );
//
//        preg_match( '|<target>(.*?)</target>|siu' , $x , $tmp );
//        //xml validation from DomDocument replaces 'x="&lt;endcmp/>"' with 'x="&lt;endcmp/&gt;"'
//        $this->assertNotEquals( $tmp[ 1 ], $xliff[ 'files' ][ 0 ][ 'trans-units' ][ 0 ][ 'target' ][ 'raw-content' ] );
//
//    }
}
