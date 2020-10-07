<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Utils\HtmlParser;

class HtmlParserTest extends BaseTest
{
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
    public function can_parse_html_with_escape_html()
    {
        $html = '&lt;div&gt;Ciao&lt;div&gt;Ciao&lt;/div&gt;&lt;/div&gt;';

        $parsed = HtmlParser::parse($html, true);
        $this->assertCount(1, $parsed);
        $this->assertCount(1, $parsed[0]->inner_html);
    }
}
