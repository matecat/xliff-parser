<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffParserTest extends BaseTest
{
    /**
     * @test
     */
    public function return_empty_array_with_not_valid_file()
    {
        $this->assertXliffEquals('note.xml', []);
    }

//    /**
//     * @test
//     */
//    public function can_parse_xliff_v1_metadata()
//    {
//        $parsed = (new XliffParser())->toArray($this->getTestFile('file-with-notes-converted-nobase64.xliff'));
//        $attr = $parsed['files'][1]['attr'];
//
//        $this->assertCount(3, $parsed['files']);
//        $this->assertEquals($attr['source-language'], 'hy-am');
//        $this->assertEquals($attr['target-language'], 'fr-fr');
//        $this->assertEquals($attr['original'], 'Ebay-like-small-file-edited.xlf');
//    }

//    /**
//     * @test
//     */
//    public function can_parse_xliff_v1_reference()
//    {
//
//    }
//
//    /**
//     * @test
//     */
//    public function can_parse_xliff_v1_trans_units()
//    {
//
//    }
//
//    /**
//     * @test
//     */
//    public function parse_xliff_v1_and_v2_produce_the_same_output()
//    {
//
//    }
}
