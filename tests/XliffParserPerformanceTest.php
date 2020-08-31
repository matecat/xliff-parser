<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffParserPerformanceTest extends BaseTest
{
    /**
     * @test
     */
    public function can_parse_a_very_large_file()
    {
        // read a file with notes inside
        $parsed = XliffParser::xliffToArray($this->getTestFile('55K_segments_english.sdlxliff'));

        $this->assertCount(109167, $parsed['files'][1]['trans-units']);
        $this->assertEquals($parsed['files'][1]['trans-units'][1]['source']['raw-content'], '<x id="0"/>');
    }
}