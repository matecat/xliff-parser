<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffReplacerTest extends BaseTest
{
    /**
     * @test
     */
    public function parses_with_no_errors()
    {
        $data = [
            [
                'sid' => 1,
                'segment' => 'World',
                'internal_id' => 'u1',
                'mrk_id' => 3232,
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Mondo',
                'status' => 'TRANSLATED',
                'eq_word_count' => 123,
                'raw_word_count' => 456,

            ],
            [
                'sid' => 2,
                'segment' => 'World2',
                'internal_id' => 'u2',
                'mrk_id' => 3232,
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Mondo2',
                'status' => 'TRANSLATED',
                'eq_word_count' => 54353,
                'raw_word_count' => 54354,
            ],
        ];

        $transUnits = [];

        foreach ( $data as $i => $k ) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            $internalId = $k[ 'internal_id' ];

            $transUnits[ $internalId ] [] = $i;

            $data[ 'matecat|' . $internalId ] [] = $i;
        }

//        $inputFile = __DIR__.'/../tests/files/file-with-notes-converted-nobase64.xliff';
//        $outputFile = __DIR__.'/../tests/files/output/file-with-notes-converted-nobase64.xliff';

        $inputFile = __DIR__.'/../tests/files/sample-20.xlf';
        $outputFile = __DIR__.'/../tests/files/output/sample-20.xlf';

        XliffParser::replaceTranslation(
                $inputFile,
                $data,
                $transUnits,
        'fr-fr',
                $outputFile);

    }
}
