<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\XliffParser;
use PHPUnit\Framework\Attributes\Test;

class TradosFilesTest extends Base
{
    #[Test]
    public function can_insert_an_empty_target_with_segment_marked_with_translation_no()
    {
        $data = [
            [
                'sid' => 1,
                'segment' => 'If the watch been idle for 60 seconds or more, the backlight can only be activated by a button press.',
                'internal_id' => '5a5c6ae0-b256-4929-a7ae-4d826323bc40',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Traduzione a caso senza alcun senso.',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ]
        ];

        $transUnits = [];

        foreach ($data as $i => $k) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            $internalId = $k['internal_id'];

            $transUnits[$internalId] [] = $i;

            $data['matecat|' . $internalId] [] = $i;
        }

        (new XliffParser())->replaceTranslation(
                $this->getTestFilePath('S9_Backlight.xls.ttx (3).sdlxliff'),
                $data,
                $transUnits,
                'it-it',
                $this->getTestFilePath('output/S9_Backlight.xls.ttx (3).sdlxliff'),
                false
            );
        $output = (new XliffParser())->xliffToArray($this->getTestFile('output/S9_Backlight.xls.ttx (3).sdlxliff'));

        $this->assertEquals(
            'Traduzione a caso senza alcun senso.',
            $output['files'][1]['trans-units'][10]['target']['raw-content']
        );
    }
}