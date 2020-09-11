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

        $this->assertEquals('3-tu2', $attr['id']);
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
    public function parses_and_set_the_translations()
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
                        'translation' => 'Ciao',
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

        $transUnits = $this->getTransUnitsForReplacementTest($data);
        $inputFile = __DIR__.'/../tests/files/uber/55384cd-uber-sites-en_us-ar-H_STRIPPED.xlf';
        $outputFile = __DIR__.'/../tests/files/uber/output/55384cd-uber-sites-en_us-ar-H_STRIPPED.xlf';
        $targetLang = 'it-it';

        (new XliffParser())->replaceTranslation($inputFile, $data, $transUnits, $targetLang, $outputFile);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));

        $this->assertEquals('Ciao', $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
        $this->assertEquals('Ricevuto!', $output['files'][1]['trans-units'][2]['target']['raw-content'][0]);
        $this->assertEquals('Riprodurre video', $output['files'][1]['trans-units'][3]['target']['raw-content'][0]);
    }

    /**
     * @test
     */
    public function parses_and_set_the_translations_with_tu_id_0()
    {
        $data = [
                [
                        'sid' => 0,
                        'segment' => 'English',
                        'internal_id' => 0,
                        'mrk_id' => '',
                        'prev_tags' => '',
                        'succ_tags' => '',
                        'mrk_prev_tags' => '',
                        'mrk_succ_tags' => '',
                        'translation' => 'Italiano',
                        'status' => TranslationStatus::STATUS_NEW,
                        'eq_word_count' => 100,
                        'raw_word_count' => 200,
                ],
        ];

        $transUnits = $this->getTransUnitsForReplacementTest($data);
        $inputFile = __DIR__.'/../tests/files/uber/test_Localization_Manual Template for Global - Wrong courier process-en_us-mr_in-H_STRIPPED.xlf';
        $outputFile = __DIR__.'/../tests/files/uber/output/test_Localization_Manual Template for Global - Wrong courier process-en_us-mr_in-H_STRIPPED.xlf';
        $targetLang = 'it-it';

        (new XliffParser())->replaceTranslation($inputFile, $data, $transUnits, $targetLang, $outputFile);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));

        $this->assertEquals('Italiano', $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    /**
     * @test
     */
    public function parses_and_set_the_translations_with_tu_with_bunch_of_segments()
    {
        $data = [
            [
                'sid' => 2,
                'segment' => 'Uber Eats - We have been informed that someone else has used your account.',
                'internal_id' => 2,
                'mrk_id' => 0,
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Italiano 2',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 20,
                'raw_word_count' => 5,
            ],
            [
                'sid' => 3,
                'segment' => 'This violates the terms of your Services Agreement with Uber.',
                'internal_id' => 2,
                'mrk_id' => 0,
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Italiano 3',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 10,
                'raw_word_count' => 5,
            ],
            [
                'sid' => 4,
                'segment' => 'If we receive multiple reports of this nature, your account will be suspended.',
                'internal_id' => 2,
                'mrk_id' => 0,
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Italiano 4',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 100,
                'raw_word_count' => 200,
            ],
            [
                'sid' => 5,
                'segment' => 'Thank you for taking this notification into account in your next deliveries.',
                'internal_id' => 2,
                'mrk_id' => 0,
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Italiano 5',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 100,
                'raw_word_count' => 200,
            ],
        ];

        $transUnits = $this->getTransUnitsForReplacementTest($data);

        $inputFile = __DIR__.'/../tests/files/uber/test_Localization_Manual Template for Global - Wrong courier process-en_us-mr_in-H_STRIPPED_MULTI.xlf';
        $outputFile = __DIR__.'/../tests/files/uber/output/test_Localization_Manual Template for Global - Wrong courier process-en_us-mr_in-H_STRIPPED_MULTI.xlf';
        $targetLang = 'it-it';

        (new XliffParser())->replaceTranslation($inputFile, $data, $transUnits, $targetLang, $outputFile);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));

        $this->assertEquals('Italiano 2', $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
        $this->assertEquals('Italiano 3', $output['files'][1]['trans-units'][1]['target']['raw-content'][1]);
        $this->assertEquals('Italiano 4', $output['files'][1]['trans-units'][1]['target']['raw-content'][2]);
        $this->assertEquals('Italiano 5', $output['files'][1]['trans-units'][1]['target']['raw-content'][3]);
    }
}