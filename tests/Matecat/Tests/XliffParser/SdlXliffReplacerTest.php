<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffReplacer\XliffReplacerFactory;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use PHPUnit\Framework\Attributes\Test;

class SdlXliffReplacerTest extends Base
{
    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_sdlxliff_with_correct_trailing_spaces(): void
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

        $inputFile = $this->getTestFilePath('sdlxliff/piazza.sdlxliff');
        $outputFile = $this->getTestFilePath('output/piazza.sdlxliff');

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);
        $output = $xliffParser->xliffToArray((string)file_get_contents($outputFile));

        $segTarget = $output['files'][1]['trans-units'][1]['seg-target'];

        $this->assertEquals('“Bla bla bla. ', $segTarget[0]['raw-content']);
        $this->assertEquals('Bla bla bla. ', $segTarget[1]['raw-content']);
        $this->assertEquals('Bla bla bla. ', $segTarget[2]['raw-content']);
        $this->assertEquals('Altra traduzione ', $segTarget[3]['raw-content']);
        $this->assertEquals('Marianne Werefkin  ', $segTarget[4]['raw-content']);
    }

    /**
     * @covers \Matecat\XliffParser\XliffReplacer\XliffSdl
     *
     * Test Xliff12 with negative mrk_id (covers line 102)
     */
    #[Test]
    public function xliff12_replace_translation_with_japanese(): void
    {
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'An English string',
                'internal_id' => 'aee647b1-0f14-4091-aada-813488be9fb7',
                'mrk_id' => '-1', // Negative mrk_id that is not null
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => '      __FAKE_TAG_END__',
                'translation' => '「私は静かな広場を渡っています。',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '3.00',
                'raw_word_count' => '3.00',
            ],
        ]);

        $inputFile = $this->getTestFilePath('sdlxliff/simple.sdlxliff');
        $outputFile = sys_get_temp_dir() . '/simple.sdlxliff';

        try {
            $replacer = XliffReplacerFactory::getInstance(
                $inputFile,
                $data['data'],
                $data['transUnits'],
                'ja-JP',
                $outputFile,
                false
            );
            $replacer->replaceTranslation();

            $this->assertFileExists($outputFile);
            $outputContent = (string)file_get_contents($outputFile);
            // Verify the translation was applied
            $this->assertMatchesRegularExpression('~<mrk[^>]+>「私は静かな広場を渡っています。__FAKE_TAG_END__</mrk>~u', $outputContent);
        } finally {
            @unlink($outputFile);
        }
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_insert_an_empty_target_with_segment_marked_with_translation_no(): void
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

        /** @var array<string, array<int, int>> $transUnits */
        $transUnits = [];

        foreach ($data as $i => $k) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            /** @var string $internalId */
            $internalId = $k['internal_id'];

            $transUnits[$internalId][] = $i;

            $data['matecat|' . $internalId][] = $i;
        }

        (new XliffParser())->replaceTranslation(
            $this->getTestFilePath('sdlxliff/S9_Backlight.xls.ttx (3).sdlxliff'),
            $data, // @phpstan-ignore argument.type
            $transUnits,
            'it-it',
            $this->getTestFilePath('output/S9_Backlight.xls.ttx (3).sdlxliff')
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile('output/S9_Backlight.xls.ttx (3).sdlxliff'));

        $this->assertEquals(
            'Traduzione a caso senza alcun senso.',
            $output['files'][1]['trans-units'][10]['target']['raw-content']
        );
    }

}
