<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Utils\Constants\TranslationStatus;
use Matecat\XliffParser\XliffParser;

/**
 * This is a test specific for some features of Uber files
 *
 * Class UberFilesTest
 * @package Matecat\XliffParser\Tests
 */
class UberFilesTest extends BaseTest
{
    /**
     * @test
     */
    public function can_extract_tGroupBegin_and_tGroupEnd()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber/SpotCheck-en_us-es_419-H.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];
        $attr = $units[2]['attr'];

        $this->assertEquals(1, $attr['tGroupBegin']);
        $this->assertEquals(3, $attr['tGroupEnd']);
    }

    /**
     * @test
     */
    public function can_extract_additionalTagData()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber/55384cd-uber-sites-en_us-ar-H.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $additionalTagData = $units[5]['additional-tag-data'];

        $this->assertCount(2, $additionalTagData);
        $this->assertEquals($additionalTagData[ 0 ][ 'attr' ][ 'id' ], 'source1');
        $this->assertEquals($additionalTagData[ 1 ][ 'attr' ][ 'id' ], 'source2');
        $this->assertEquals($additionalTagData[ 0 ][ 'raw-content' ][ 'tagId' ], 1);
        $this->assertEquals($additionalTagData[ 0 ][ 'raw-content' ][ 'type' ], 'code');
        $this->assertEquals($additionalTagData[ 1 ][ 'raw-content' ][ 'tagId' ], 2);
        $this->assertEquals($additionalTagData[ 1 ][ 'raw-content' ][ 'type' ], 'code');
    }

    /**
     * @test
     */
    public function parses_and_set_the_correct_segment_state()
    {
        $data = $this->getData();
        $inputFile = __DIR__.'/../tests/files/uber/55384cd-uber-sites-en_us-ar-H_STRIPPED.xlf';
        $outputFile = __DIR__.'/../tests/files/uber/output/55384cd-uber-sites-en_us-ar-H_STRIPPED.xlf';
        $targetLang = 'it-it';

        //(new XliffParser())->replaceTranslation($inputFile, $data['data'], $data['transUnits'], $targetLang, $outputFile);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));

        var_dump(
                $output
        );


        //$expected = '|||UNTRANSLATED_CONTENT_START|||&lt;pc id="1"&gt;Hello <mrk id="m2" type="term">World</mrk> !&lt;/pc&gt;|||UNTRANSLATED_CONTENT_END|||';

        //$this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content']);
    }

    /**
     * @return array
     */
    private function getData()
    {
        $data = [
            [
                'sid' => 1,
                'segment' => 'Uber Eats Online Ordering Sales Channel Addendum Data Processing Agreement',
                'internal_id' => 'tu-1',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Uber Eats Online Ordering Sales Channel Addendum Data Processing Agreement',
                'status' => TranslationStatus::STATUS_NEW,
                'eq_word_count' => 100,
                'raw_word_count' => 200,
            ],
            [
                'sid' => 2,
                'segment' => 'Got it',
                'internal_id' => 'tu-2',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Ricevuto!',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 200,
                'raw_word_count' => 300,
            ],
            [
                'sid' => 3,
                'segment' => 'Play video',
                'internal_id' => 'tu-3',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Riprodurre video',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 200,
                'raw_word_count' => 300,
            ],
        ];

        $transUnits = [];

        foreach ($data as $i => $k) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            $internalId = $k[ 'internal_id' ];

            $transUnits[ $internalId ] [] = $i;

            $data[ 'matecat|' . $internalId ] [] = $i;
        }

        return [
                'data' => $data,
                'transUnits' => $transUnits,
        ];
    }
}