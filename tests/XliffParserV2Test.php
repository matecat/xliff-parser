<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffParserV2Test extends BaseTest
{

    /**
     * @test
     */
    public function can_parse_xliff_v2_notes()
    {
        $parsed = (new XliffParser())->toArray($this->getTestFile('sample-20.xlf'));
        $notes = $parsed['files'][1]['notes'];



//        $this->assertCount(1, $notes);
//        $this->assertEquals($notes[0]['raw-content'], 'note for unit');

        die();
    }

}