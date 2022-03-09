<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Utils\HtmlParser;

class HtmlParserTest extends BaseTest
{
    /**
     * @test
     */
    public function can_parse_a_valid_html5_page()
    {
        $string = file_get_contents(__DIR__.'/files/page.html');
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertCount(2, $parsed[0]->inner_html);
        $this->assertEquals('head', $parsed[0]->inner_html[0]->tagname);
        $this->assertEquals('body', $parsed[0]->inner_html[1]->tagname);
        $this->assertCount(4, $parsed[0]->inner_html[0]->inner_html);
        $this->assertCount(1, $parsed[0]->inner_html[1]->inner_html);
        $this->assertCount(5, $parsed[0]->inner_html[1]->inner_html[0]->inner_html);
    }

    /**
     * @test
     */
    public function can_parse_html_with_greater_than_symbol()
    {
        $string = '<div id="1">Ciao > ciao<div id="2"></div></div>';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('Ciao > ciao', $parsed[0]->stripped_text);
        $this->assertEquals('1', $parsed[0]->attributes['id']);
        $this->assertEquals('2', $parsed[0]->inner_html[0]->attributes['id']);
    }

    /**
     * @test
     */
    public function can_parse_html_with_less_than_symbol()
    {
        $string = '<div id="1">< Ciao <<div id="2"></div></div>';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('< Ciao <', $parsed[0]->stripped_text);
        $this->assertEquals('1', $parsed[0]->attributes['id']);
        $this->assertEquals('2', $parsed[0]->inner_html[0]->attributes['id']);
    }

    /**
     * @test
     */
    public function can_parse_html_with_greater_than_and_less_than_symbols()
    {
        $string = '<div id="1">Ciao <> ciao<div id="2"></div></div>';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('Ciao <> ciao', $parsed[0]->stripped_text);
        $this->assertEquals('1', $parsed[0]->attributes['id']);
        $this->assertEquals('2', $parsed[0]->inner_html[0]->attributes['id']);
    }

    /**
     * @test
     */
    public function can_parse_html_with_greater_than_and_less_than_symbols_in_inversed_order()
    {
        $string = '<div id="1">Ciao > < ciao<div id="2"></div></div>';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('Ciao > < ciao', $parsed[0]->stripped_text);
        $this->assertEquals('1', $parsed[0]->attributes['id']);
        $this->assertEquals('2', $parsed[0]->inner_html[0]->attributes['id']);
    }

    /**
     * @test
     */
    public function can_extract_inner_text()
    {
        $string = '<div class=\'text\'>questo è un testo</div>';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('text', $parsed[0]->attributes['class']);
        $this->assertEquals('questo è un testo', $parsed[0]->original_text);
        $this->assertEquals('<div class=\'text\'>', $parsed[0]->start);
        $this->assertEquals('</div>', $parsed[0]->end);
    }

    /**
     * @test
     */
    public function can_extract_inner_text_with_nested_html_content()
    {
        $string = '<div class=\'text\'><div>ciao questo è un testo</div> con del contenuto html.</div>';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('text', $parsed[0]->attributes['class']);
        $this->assertEquals('<div>ciao questo è un testo</div> con del contenuto html.', $parsed[0]->original_text);
        $this->assertEquals('ciao questo è un testo con del contenuto html.', $parsed[0]->stripped_text);
    }

    /**
     * @test
     */
    public function can_parse_a_string_with_escaped_single_quotes()
    {
        $string = '<div class=\'text\'></div>';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('text', $parsed[0]->attributes['class']);
        $this->assertEquals('', $parsed[0]->original_text);
    }

    /**
     * @test
     */
    public function can_parse_a_string_with_escaped_double_quotes()
    {
        $string = '<div class=\"text\"></div>';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('text', $parsed[0]->attributes['class']);
    }

    /**
     * @test
     */
    public function can_parse_a_string_containing_less_than_sign()
    {
        $string = 'In questa frase ci sono caratteri \'nie ontsnap\' nie! Per vedere come si comporta {+ o -} il filtro Markdown in presenza di #. Anche se non \u00e8_detto_che 2 * 2 &lt;5 con
         &lt;ph id=\"1\" canCopy=\"no\" canDelete=\"no\" dataRef=\"d1\"\/&gt;&lt;ph id=\"2\" canCopy=\"no\" canDelete=\"no\" dataRef=\"d2\"\/&gt;.';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(2, $parsed);
        $this->assertEquals('d1', $parsed[0]->attributes['dataRef']);
        $this->assertEquals('d2', $parsed[1]->attributes['dataRef']);
    }

    /**
     * @test
     */
    public function can_parse_a_string_containing_html()
    {
        $string = 'Testo libero contenente &lt;ph id="mtc_1" equiv-text="base64:Jmx0O3BjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow=="/&gt;corsivo&lt;ph id="mtc_2" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;, &lt;ph id="mtc_3" equiv-text="base64:Jmx0O3BjIGlkPSIyIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDIiJmd0Ow=="/&gt;grassetto&lt;ph id="mtc_4" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;, &lt;ph id="mtc_5" equiv-text="base64:Jmx0O3BjIGlkPSIzIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow=="/&gt;&lt;ph id="mtc_6" equiv-text="base64:Jmx0O3BjIGlkPSI0IiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDIiJmd0Ow=="/&gt;grassetto + corsivo&lt;ph id="mtc_7" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;&lt;ph id="mtc_8" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt; e &lt;ph id="mtc_9" equiv-text="base64:Jmx0O3BjIGlkPSI1IiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDMiIGRhdGFSZWZTdGFydD0iZDMiJmd0Ow=="/&gt;larghezza fissa&lt;ph id="mtc_10" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;.';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(10, $parsed);
    }

    /**
     * @test
     */
    public function can_parse_html()
    {
        $html = '<div class="row col-md-12" id="test">Ciao</div><div><h1 class="text-center">Title</h1><p>First p</p><p>Second p</p><p>Third p <span>with nested span</span></p></div>';
        $parsed = HtmlParser::parse($html);

        $this->assertCount(2, $parsed);
        $this->assertEquals('Ciao', $parsed[0]->inner_html);
        $this->assertCount(4, $parsed[1]->inner_html);

        $html = '<div>Ciao</div><ph id="id" dataRef="d1" />';
        $parsed = HtmlParser::parse($html);

        $this->assertCount(2, $parsed);
    }

    /**
     * @test
     */
    public function can_parse_html_with_escaped_html()
    {
        $html = '&lt;div&gt;Ciao&lt;div&gt;Ciao&lt;/div&gt;&lt;/div&gt;';
        $parsed = HtmlParser::parse($html);

        $this->assertCount(1, $parsed);
        $this->assertCount(1, $parsed[0]->inner_html);
        $this->assertEquals('Ciao&lt;div&gt;Ciao&lt;/div&gt;', $parsed[0]->original_text);
        $this->assertEquals('CiaoCiao', $parsed[0]->stripped_text);
    }

    /**
     * @test
     */
    public function can_parse_html_with_nested_escaped_html()
    {
        $html = 'Testo libero contenente &lt;pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1"&gt;corsivo&lt;/pc&gt;, &lt;pc id="2" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2"&gt;grassetto&lt;/pc&gt;, &lt;pc id="3" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1"&gt;&lt;pc id="4" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2"&gt;grassetto + corsivo&lt;/pc&gt;&lt;/pc&gt; e &lt;pc id="5" canCopy="no" canDelete="no" dataRefEnd="d3" dataRefStart="d3"&gt;larghezza fissa&lt;/pc&gt;.';
        $parsed = HtmlParser::parse($html);

        $this->assertEquals($parsed[2]->inner_html[0]->node, '&lt;pc id="4" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2"&gt;grassetto + corsivo&lt;/pc&gt;');
    }

    /**
     * @test
     */
    public function can_parse_a_xml()
    {
        $xml = file_get_contents(__DIR__.'/files/note.xml');
        $parsed = HtmlParser::parse($xml);

        $this->assertCount(4, $parsed[0]->inner_html);
        $this->assertEquals('Tove', $parsed[0]->inner_html[0]->inner_html);
        $this->assertEquals('Jani', $parsed[0]->inner_html[1]->inner_html);
        $this->assertEquals('Reminder', $parsed[0]->inner_html[2]->inner_html);
        $this->assertEquals('Don\'t forget me this weekend!', $parsed[0]->inner_html[3]->inner_html);
    }

    /**
     * @test
     */
    public function can_parse_a_xliff()
    {
        $xliff = file_get_contents(__DIR__.'/files/no-target.xliff');
        $parsed = HtmlParser::parse($xliff);

        $note = $parsed[0]->inner_html[0]->inner_html[0]->inner_html[0]->inner_html[0];
        $tu = $parsed[0]->inner_html[0]->inner_html[0]->inner_html[0]->inner_html[1];

        $this->assertEquals($note->tagname, 'note');
        $this->assertEquals($note->inner_html, '');
        $this->assertEquals($tu->tagname, 'trans-unit');
        $this->assertEquals($tu->attributes['id'], 'pendo-image-e3aaf7b7|alt');
    }

    /**
     * @test
     */
    public function can_escape_correctly_nodes_containing_special_characters()
    {
        // this string contains ’
        $string = '&lt;pc id="source4" dataRefStart="source4"&gt;The rider can’t tell if the driver matched the profile picture.&lt;/pc&gt;';
        $parsed = HtmlParser::parse($string);

        $pc = $parsed[0];

        $this->assertEquals($pc->node, '&lt;pc id="source4" dataRefStart="source4"&gt;The rider can’t tell if the driver matched the profile picture.&lt;/pc&gt;');
        $this->assertEquals($pc->original_text, 'The rider can’t tell if the driver matched the profile picture.');
        $this->assertEquals($pc->stripped_text, 'The rider can’t tell if the driver matched the profile picture.');

        // this string contains > inside text
        $string = '&lt;pc id="source4" dataRefStart="source4"&gt;Questa stringa contiene un > a stringa.&lt;/pc&gt;';
        $parsed = HtmlParser::parse($string);

        $pc = $parsed[0];
        $this->assertEquals($pc->node, '&lt;pc id="source4" dataRefStart="source4"&gt;Questa stringa contiene un > a stringa.&lt;/pc&gt;');
        $this->assertEquals($pc->original_text, 'Questa stringa contiene un > a stringa.');
        $this->assertEquals($pc->stripped_text, 'Questa stringa contiene un > a stringa.');
    }

    /**
     * @test
     */
    public function can_parse_html_with_not_closed_html_tags()
    {
        $string = 'Ciao <div id="3" >ciao</div><div>';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('ciao', $parsed[0]->stripped_text);
        $this->assertEquals('3', $parsed[0]->attributes['id']);
    }

    /**
     * @test
     */
    public function can_parse_escaped_html_with_greater_than_symbol()
    {
        $string = 'Ödemenizin kapatılması için Ödemenizin kapatılması için &lt;Outage&gt; beklemenizi rica ediyoruz. &lt;ph dataRef="source1" id="source1"/&gt;';
        $parsed = HtmlParser::parse($string);

        $this->assertCount(1, $parsed);
        $this->assertEquals('source1', $parsed[0]->attributes['id']);
        $this->assertEquals('source1', $parsed[0]->attributes['dataRef']);
    }
}


