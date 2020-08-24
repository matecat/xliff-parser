<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffParserTest extends BaseTest
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





    /**
     * @test
     */
    public function return_empty_array_with_not_valid_file()
    {
        $this->assertXliffEquals('note.xml', []);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v1_metadata()
    {
        $parsed = (new XliffParser())->toArray($this->getTestFile('file-with-notes-converted-nobase64.xliff'));
        $attr = $parsed['files'][1]['attr'];

        $this->assertCount(3, $parsed['files']);
        $this->assertEquals($attr['source-language'], 'hy-am');
        $this->assertEquals($attr['target-language'], 'fr-fr');
        $this->assertEquals($attr['original'], 'Ebay-like-small-file-edited.xlf');
    }

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
    public function can_parse_xliff_v2_trans_units()
    {
        $parsed = (new XliffParser())->toArray($this->getTestFile('uber-v2.xliff'));
        $transUnits = $parsed['files'][1]['trans-units'];

        $this->assertCount(9, $transUnits);

        $firstTransUnit = $transUnits[1];

        $notes = [
                'note for unit',
                'another note for file.',
                [
                        "key" => "01d35857-b9bd-4835-8db1-40febcdcc8e9",
                        'key-note' => 'Repo: &lt;a href ="https://i18n.uberinternal.com/rosetta2/repo/rtapi/keys" target="_blank"&gt;rtapi&lt;/a&gt; Key Name: &lt;a href="https://i18n.uberinternal.com/rosetta2/repo/rtapi/key/driver_tasks.delivery_reminders.order.wda.title/overview" target="_blank"&gt;driver_tasks.delivery_reminders.order.wda.title&lt;/a&gt; Description: &lt;font color="blue"&gt;Title for delivery reminders when an eater has changed the dropoff location for an order&lt;/font&gt;'
                ]
        ];

        $this->assertEquals($firstTransUnit['attr']['id'], 0);
        $this->assertEquals($firstTransUnit['notes'], $notes);
        $this->assertContains('<data id="source1">${recipientName}</data>', $firstTransUnit['original-data'][0]);
    }



//    /**
//     * @test
//     */
//    public function parse_xliff_v1_and_v2_produce_the_same_output()
//    {
//
//    }
}