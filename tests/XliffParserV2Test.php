<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffParserV2Test extends BaseTest
{
    /**
     * @test
     */
    public function can_parse_xliff_v2_metadata()
    {
        $parsed = (new XliffParser())->toArray($this->getTestFile('uber-v2.xliff'));
        $attr = $parsed['files'][1]['attr'];

        $this->assertCount(3, $attr);
        $this->assertEquals($attr['source-language'], 'en-us');
        $this->assertEquals($attr['target-language'], 'el-gr');
        $this->assertEquals($attr['original'], '389108a4-rtapi.xml');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_notes()
    {
        $parsed = (new XliffParser())->toArray($this->getTestFile('sample-20.xlf'));
        $notes = $parsed['files'][1]['notes'];

        $this->assertCount(3, $notes);
        $this->assertEquals($notes[0]['raw-content'], 'note for file.');
        $this->assertEquals($notes[1]['raw-content'], 'note2 for file.');
        $this->assertEquals($notes[2]['json'], '{
                    "key": "value",
                    "key2": "value2",
                    "key3": "value3"
                }');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_metadata()
    {
        $parsed = (new XliffParser())->toArray($this->getTestFile('sample-20.xlf'));
        $units = $parsed['files'][1]['trans-units'];

        $this->assertCount(2, $units);
        $this->assertEquals($units[1]['attr']['id'], 'u1');
        $this->assertEquals($units[1]['attr']['translate'], 'test');
        $this->assertEquals($units[2]['attr']['id'], 'u2');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_originalData()
    {
        $parsed = (new XliffParser())->toArray($this->getTestFile('uber-v2.xliff'));
        $units = $parsed['files'][1]['trans-units'];

        $this->assertEquals($units[5]['original-data'][0]['raw-content'], '${redemptionLimit}');
        $this->assertEquals($units[5]['original-data'][0]['attr']['id'], 'source1');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_notes()
    {

    }
}