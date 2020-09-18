<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffReplacer\XliffReplacerCallbackInterface;

class XliffReplacerTest extends BaseTest
{
    /**
     * @test
     */
    public function parses_with_no_errors()
    {
        $data = $this->getData();
        $inputFile = __DIR__.'/../tests/files/sample-20.xlf';
        $outputFile = __DIR__.'/../tests/files/output/sample-20.xlf';

        (new XliffParser())->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'fr-fr', $outputFile, false, new DummyXliffReplacerCallback());
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));
        $expected = '&lt;pc id="1"&gt;Buongiorno al <mrk id="m2" type="term">Mondo</mrk> !&lt;/pc&gt;';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    /**
     * @test
     */
    public function parses_with_consistency_errors()
    {
        $data = $this->getData();
        $inputFile = __DIR__.'/../tests/files/sample-20.xlf';
        $outputFile = __DIR__.'/../tests/files/output/sample-20.xlf';

        (new XliffParser())->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'fr-fr', $outputFile, false, new DummyXliffReplacerCallbackWhichReturnTrue());
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));
        $expected = '|||UNTRANSLATED_CONTENT_START|||&lt;pc id="1"&gt;Hello <mrk id="m2" type="term">World</mrk> !&lt;/pc&gt;|||UNTRANSLATED_CONTENT_END|||';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    /**
     * @return array
     */
    private function getData()
    {
        $data = [
            [
                'sid' => 1,
                'segment' => '<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>',
                'internal_id' => 'u1',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => '<pc id="1">Buongiorno al <mrk id="m2" type="term">Mondo</mrk> !</pc>',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 100,
                'raw_word_count' => 200,
            ],
            [
                'sid' => 2,
                'segment' => '<pc id="1">Hello <mrk id="m2" type="term">World2</mrk> !</pc>',
                'internal_id' => 'u2',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => '<pc id="2">Buongiorno al <mrk id="m2" type="term">Mondo2</mrk> !</pc>',
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

class DummyXliffReplacerCallback implements XliffReplacerCallbackInterface
{
    /**
     * @inheritDoc
     */
    public function thereAreErrors($segment, $translation)
    {
        return false;
    }
}

class DummyXliffReplacerCallbackWhichReturnTrue implements XliffReplacerCallbackInterface
{
    /**
     * @inheritDoc
     */
    public function thereAreErrors($segment, $translation)
    {
        return true;
    }
}
