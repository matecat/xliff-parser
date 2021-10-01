<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffReplacer\XliffReplacerCallbackInterface;

class SdlXliffReplacerTest extends BaseTest
{
    /**
     * @test
     */
    public function can_replace_a_sdlxliff_with_correct_trailing_spaces()
    {
        $data = $this->getData([
                [
                        'sid' => 1966979792,
                        'segment' => '“Sto attraversando la piazza silenziosa. ',
                        'internal_id' => 'aee647b1-0f14-4091-aada-813488be9fb7',
                        'mrk_id' => '1',
                        'prev_tags' => '<g id="2">',
                        'succ_tags' => '',
                        'mrk_prev_tags' => '',
                        'mrk_succ_tags' => '',
                        'translation' => '“Bla bla bla. ',
                        'status' => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count' => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid' => 1966979792,
                        'segment' => 'Il lago giace calmo e sereno. ',
                        'internal_id' => 'aee647b1-0f14-4091-aada-813488be9fb7',
                        'mrk_id' => '2',
                        'prev_tags' => '',
                        'succ_tags' => '',
                        'mrk_prev_tags' => '',
                        'mrk_succ_tags' => '',
                        'translation' => 'Bla bla bla. ',
                        'status' => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count' => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid' => 3,
                        'segment' => 'Le bianche case pallidamente risplendono sulla collina. ',
                        'internal_id' => 'aee647b1-0f14-4091-aada-813488be9fb7',
                        'mrk_id' => '3',
                        'prev_tags' => '',
                        'succ_tags' => '',
                        'mrk_prev_tags' => '',
                        'mrk_succ_tags' => '',
                        'translation' => 'Bla bla bla. ',
                        'status' => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count' => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid' => 4,
                        'segment' => 'Gatti piccoli e grossi attraversano il mio cammino.” ',
                        'internal_id' => 'aee647b1-0f14-4091-aada-813488be9fb7',
                        'mrk_id' => '4',
                        'prev_tags' => '',
                        'succ_tags' => ' <g id="5">',
                        'mrk_prev_tags' => '',
                        'mrk_succ_tags' => '',
                        'translation' => 'Altra traduzione ',
                        'status' => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count' => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid' => 5,
                        'segment' => 'Marianne Werefkin  ',
                        'internal_id' => 'aee647b1-0f14-4091-aada-813488be9fb7',
                        'mrk_id' => '5',
                        'prev_tags' => '',
                        'succ_tags' => '  </g></g>',
                        'mrk_prev_tags' => '',
                        'mrk_succ_tags' => '',
                        'translation' => 'Marianne Werefkin  ',
                        'status' => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count' => 1,
                        'raw_word_count' => 1,
                ],
        ]);

        $inputFile = __DIR__.'/../tests/files/sdlxliff/piazza.sdlxliff';
        $outputFile = __DIR__.'/../tests/files/output/sdlxliff/piazza.sdlxliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);
        $output = $xliffParser->xliffToArray(file_get_contents($outputFile));

        $segTarget = $output['files'][1]['trans-units'][1]['seg-target'];

        $this->assertEquals('“Bla bla bla. ', $segTarget[0]['raw-content']);
        $this->assertEquals('Bla bla bla. ', $segTarget[1]['raw-content']);
        $this->assertEquals('Bla bla bla. ', $segTarget[2]['raw-content']);
        $this->assertEquals('Altra traduzione ', $segTarget[3]['raw-content']);
        $this->assertEquals('Marianne Werefkin  ', $segTarget[4]['raw-content']);
    }
}
