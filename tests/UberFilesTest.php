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
    public function can_replace_translation_with_a_real_matecat_example()
    {
        $transUnits = [
                0 =>
                        [
                                0 => 0,
                        ],
                1 =>
                        [
                                0 => 1,
                        ],
                2 =>
                        [
                                0 => 2,
                        ],
                3 =>
                        [
                                0 => 3,
                        ],
                4 =>
                        [
                                0 => 4,
                                1 => 5,
                        ],
                5 =>
                        [
                                0 => 6,
                        ],
        ];

        $data = [
                0           =>
                        [
                                'sid'            => '13570',
                                'segment'        => 'Uber Eats Online Ordering Sales Channel Addendum Data Processing Agreement',
                                'internal_id'    => '0',
                                'mrk_id'         => '0',
                                'prev_tags'      => '',
                                'succ_tags'      => '',
                                'mrk_prev_tags'  => '',
                                'mrk_succ_tags'  => '',
                                'translation'    => 'Uber Eats Online Ordering Sales Channel Addendum Accordo sul trattamento dei dati',
                                'status'         => 'TRANSLATED',
                                'eq_word_count'  => '8.00',
                                'raw_word_count' => '10.00',
                        ],
                1           =>
                        [
                                'sid'            => '13571',
                                'segment'        => 'Got it',
                                'internal_id'    => '1',
                                'mrk_id'         => '0',
                                'prev_tags'      => '',
                                'succ_tags'      => '',
                                'mrk_prev_tags'  => '',
                                'mrk_succ_tags'  => '',
                                'translation'    => 'Ho capito',
                                'status'         => 'TRANSLATED',
                                'eq_word_count'  => '0.60',
                                'raw_word_count' => '2.00',
                        ],
                2           =>
                        [
                                'sid'            => '13572',
                                'segment'        => 'Play video',
                                'internal_id'    => '2',
                                'mrk_id'         => '0',
                                'prev_tags'      => '',
                                'succ_tags'      => '',
                                'mrk_prev_tags'  => '',
                                'mrk_succ_tags'  => '',
                                'translation'    => 'Riproduci video',
                                'status'         => 'TRANSLATED',
                                'eq_word_count'  => '0.60',
                                'raw_word_count' => '2.00',
                        ],
                3           =>
                        [
                                'sid'            => '13573',
                                'segment'        => 'Opt-Out',
                                'internal_id'    => '3',
                                'mrk_id'         => '0',
                                'prev_tags'      => '',
                                'succ_tags'      => '',
                                'mrk_prev_tags'  => '',
                                'mrk_succ_tags'  => '',
                                'translation'    => 'Opzione di revoca',
                                'status'         => 'TRANSLATED',
                                'eq_word_count'  => '0.60',
                                'raw_word_count' => '1.00',
                        ],
                4           =>
                        [
                                'sid'            => '13574',
                                'segment'        => 'This website uses third party cookies in order to serve you relevant ads on other websites.',
                                'internal_id'    => '4',
                                'mrk_id'         => '0',
                                'prev_tags'      => '',
                                'succ_tags'      => '',
                                'mrk_prev_tags'  => '',
                                'mrk_succ_tags'  => '',
                                'translation'    => 'Questo sito Web utilizza cookie di terze parti per offrirti annunci pertinenti su altri siti Web.',
                                'status'         => 'TRANSLATED',
                                'eq_word_count'  => '12.80',
                                'raw_word_count' => '16.00',
                        ],
                5           =>
                        [
                                'sid'            => '13575',
                                'segment'        => 'Learn more by visiting our <ph id="source1" dataRef="source1"/>Cookie Statement<ph id="source2" dataRef="source2"/>, or opt out of third party ad cookies using the button below.',
                                'internal_id'    => '4',
                                'mrk_id'         => '1',
                                'prev_tags'      => '',
                                'succ_tags'      => '',
                                'mrk_prev_tags'  => '',
                                'mrk_succ_tags'  => '',
                                'translation'    => 'Per saperne di più, visita la nostra Dichiarazione sui cookieo disattiva i cookie pubblicitari di terze parti utilizzando il pulsante sottostante.<ph id="source1" dataRef="source1"/> <ph id="source2" dataRef="source2"/> ',
                                'status'         => 'TRANSLATED',
                                'eq_word_count'  => '15.20',
                                'raw_word_count' => '19.00',
                        ],
                6           =>
                        [
                                'sid'            => '13576',
                                'segment'        => 'Winners Get Dinners Promotion',
                                'internal_id'    => '5',
                                'mrk_id'         => '0',
                                'prev_tags'      => '',
                                'succ_tags'      => '',
                                'mrk_prev_tags'  => '',
                                'mrk_succ_tags'  => '',
                                'translation'    => 'I vincitori ottengono la promozione delle cene',
                                'status'         => 'TRANSLATED',
                                'eq_word_count'  => '3.20',
                                'raw_word_count' => '4.00',
                        ],
                'matecat|0' =>
                        [
                                0 => 0,
                        ],
                'matecat|1' =>
                        [
                                0 => 1,
                        ],
                'matecat|2' =>
                        [
                                0 => 2,
                        ],
                'matecat|3' =>
                        [
                                0 => 3,
                        ],
                'matecat|4' =>
                        [
                                0 => 4,
                                1 => 5,
                        ],
                'matecat|5' =>
                        [
                                0 => 6,
                        ],
        ];

        $inputFile = __DIR__.'/../tests/files/uber/55384cd-uber-sites-en_us-ar-H.xlf';
        $outputFile = __DIR__.'/../tests/files/uber/output/55384cd-uber-sites-en_us-ar-H.xlf';
        $targetLang = 'it-it';

        (new XliffParser())->replaceTranslation($inputFile, $data, $transUnits, $targetLang, $outputFile);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));

        $this->assertEquals('Uber Eats Online Ordering Sales Channel Addendum Accordo sul trattamento dei dati', $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    /**
     * @test
     */
    public function can_replace_target_with_source()
    {
        $data = [
            [
                'sid' => 1,
                'segment' => 'Did you collect <ph id="source1" dataRef="source1"/> from <ph id="source2" dataRef="source2"/>?',
                'internal_id' => '0',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Hai raccolto <ph id="source1" dataRef="source1"/> da <ph id="source2" dataRef="source2"/>?',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 100,
                'raw_word_count' => 200,
            ],
        ];

        $transUnits = $this->getTransUnitsForReplacementTest($data);
        $inputFile = __DIR__.'/../tests/files/uber/7cf155ce-rtapi-en_us-bn_bd-H.xlf';
        $outputFile = __DIR__.'/../tests/files/uber/output/7cf155ce-rtapi-en_us-bn_bd-H.xlf';
        $targetLang = 'it-it';

        // set $setSourceInTarget to true
        (new XliffParser())->replaceTranslation($inputFile, $data, $transUnits, $targetLang, $outputFile, true);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));

        $this->assertEquals('Did you collect <ph id="source1" dataRef="source1"/> from <ph id="source2" dataRef="source2"/>?', $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    /**
     * @test
     */
    public function can_replace_translation_with_ph()
    {
        $data = [
            [
                'sid' => 1,
                'segment' => 'Did you collect <ph id="source1" dataRef="source1"/> from <ph id="source2" dataRef="source2"/>?',
                'internal_id' => '0',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Hai raccolto <ph id="source1" dataRef="source1"/> da <ph id="source2" dataRef="source2"/>?',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 100,
                'raw_word_count' => 200,
            ],
        ];

        $transUnits = $this->getTransUnitsForReplacementTest($data);
        $inputFile = __DIR__.'/../tests/files/uber/7cf155ce-rtapi-en_us-bn_bd-H.xlf';
        $outputFile = __DIR__.'/../tests/files/uber/output/7cf155ce-rtapi-en_us-bn_bd-H.xlf';
        $targetLang = 'it-it';

        (new XliffParser())->replaceTranslation($inputFile, $data, $transUnits, $targetLang, $outputFile);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));

        $this->assertEquals('Hai raccolto <ph id="source1" dataRef="source1"/> da <ph id="source2" dataRef="source2"/>?', $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    /**
     * @test
     */
    public function can_replace_translation_in_a_large_file_with_ph()
    {
        $data = [
            [
                'sid' => 0,
                'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                'internal_id' => '0',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'ciao',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                'sid' => 1,
                'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                'internal_id' => '0',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'ciao 1',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                'sid' => 2,
                'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                'internal_id' => '0',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'ciao 2',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                    'sid' => 3,
                    'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                    'internal_id' => '0',
                    'mrk_id' => '',
                    'prev_tags' => '',
                    'succ_tags' => '',
                    'mrk_prev_tags' => '',
                    'mrk_succ_tags' => '',
                    'translation' => 'ciao 3',
                    'status' => TranslationStatus::STATUS_TRANSLATED,
                    'eq_word_count' => 1,
                    'raw_word_count' => 1,
            ],
            [
                    'sid' => 4,
                    'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                    'internal_id' => '0',
                    'mrk_id' => '',
                    'prev_tags' => '',
                    'succ_tags' => '',
                    'mrk_prev_tags' => '',
                    'mrk_succ_tags' => '',
                    'translation' => 'ciao 4',
                    'status' => TranslationStatus::STATUS_TRANSLATED,
                    'eq_word_count' => 1,
                    'raw_word_count' => 1,
            ],
            [
                    'sid' => 5,
                    'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                    'internal_id' => '0',
                    'mrk_id' => '',
                    'prev_tags' => '',
                    'succ_tags' => '',
                    'mrk_prev_tags' => '',
                    'mrk_succ_tags' => '',
                    'translation' => 'ciao 5',
                    'status' => TranslationStatus::STATUS_TRANSLATED,
                    'eq_word_count' => 1,
                    'raw_word_count' => 1,
            ],
            [
                    'sid' => 6,
                    'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                    'internal_id' => '0',
                    'mrk_id' => '',
                    'prev_tags' => '',
                    'succ_tags' => '',
                    'mrk_prev_tags' => '',
                    'mrk_succ_tags' => '',
                    'translation' => 'ciao 6',
                    'status' => TranslationStatus::STATUS_TRANSLATED,
                    'eq_word_count' => 1,
                    'raw_word_count' => 1,
            ],
            [
                    'sid' => 7,
                    'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                    'internal_id' => '0',
                    'mrk_id' => '',
                    'prev_tags' => '',
                    'succ_tags' => '',
                    'mrk_prev_tags' => '',
                    'mrk_succ_tags' => '',
                    'translation' => 'ciao 7',
                    'status' => TranslationStatus::STATUS_TRANSLATED,
                    'eq_word_count' => 1,
                    'raw_word_count' => 1,
            ],
            [
                    'sid' => 8,
                    'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                    'internal_id' => '0',
                    'mrk_id' => '',
                    'prev_tags' => '',
                    'succ_tags' => '',
                    'mrk_prev_tags' => '',
                    'mrk_succ_tags' => '',
                    'translation' => 'ciao 8',
                    'status' => TranslationStatus::STATUS_TRANSLATED,
                    'eq_word_count' => 1,
                    'raw_word_count' => 1,
            ],
            [
                    'sid' => 9,
                    'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                    'internal_id' => '0',
                    'mrk_id' => '',
                    'prev_tags' => '',
                    'succ_tags' => '',
                    'mrk_prev_tags' => '',
                    'mrk_succ_tags' => '',
                    'translation' => 'ciao 9',
                    'status' => TranslationStatus::STATUS_TRANSLATED,
                    'eq_word_count' => 1,
                    'raw_word_count' => 1,
            ],
            [
                    'sid' => 10,
                    'segment' => 'Hi <ph id="source1" dataRef="source1" />,<ph id="source2" dataRef="source2" /><ph id="source3" dataRef="source3" /><ph id="source4" dataRef="source4" /><ph id="source5" dataRef="source5" />Thanks for reaching out.<ph id="source6" dataRef="source6" /><ph id="source7" dataRef="source7" /><ph id="source8" dataRef="source8" /><ph id="source9" dataRef="source9" /><ph id="source10" dataRef="source10" />Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" /><ph id="source12" dataRef="source12" /><ph id="source13" dataRef="source13" /><ph id="source14" dataRef="source14" /><ph id="source15" dataRef="source15" />To start creating vouchers:<ph id="source16" dataRef="source16" /><ph id="source17" dataRef="source17" /><ph id="source18" dataRef="source18" /><ph id="source19" dataRef="source19" /><ph id="source20" dataRef="source20" />1.',
                    'internal_id' => '0',
                    'mrk_id' => '',
                    'prev_tags' => '',
                    'succ_tags' => '',
                    'mrk_prev_tags' => '',
                    'mrk_succ_tags' => '',
                    'translation' => 'ciao 10',
                    'status' => TranslationStatus::STATUS_TRANSLATED,
                    'eq_word_count' => 1,
                    'raw_word_count' => 1,
            ],
        ];

        $transUnits = $this->getTransUnitsForReplacementTest($data);
        $inputFile = __DIR__.'/../tests/files/uber/39f87a30-bliss_saved_reply_content-en_us-ar-PM.xlf';
        $outputFile = __DIR__.'/../tests/files/uber/output/39f87a30-bliss_saved_reply_content-en_us-ar-PM.xlf';
        $targetLang = 'it-it';

        (new XliffParser())->replaceTranslation($inputFile, $data, $transUnits, $targetLang, $outputFile);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));

        $this->assertEquals('ciao', $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
        $this->assertEquals('ciao 1', $output['files'][1]['trans-units'][1]['target']['raw-content'][1]);
        $this->assertEquals('ciao 2', $output['files'][1]['trans-units'][1]['target']['raw-content'][2]);
        $this->assertEquals('ciao 3', $output['files'][1]['trans-units'][1]['target']['raw-content'][3]);
        $this->assertEquals('ciao 4', $output['files'][1]['trans-units'][1]['target']['raw-content'][4]);
        $this->assertEquals('ciao 5', $output['files'][1]['trans-units'][1]['target']['raw-content'][5]);
        $this->assertEquals('ciao 6', $output['files'][1]['trans-units'][1]['target']['raw-content'][6]);
        $this->assertEquals('ciao 7', $output['files'][1]['trans-units'][1]['target']['raw-content'][7]);
        $this->assertEquals('ciao 8', $output['files'][1]['trans-units'][1]['target']['raw-content'][8]);
        $this->assertEquals('ciao 9', $output['files'][1]['trans-units'][1]['target']['raw-content'][9]);
        $this->assertEquals('ciao 10', $output['files'][1]['trans-units'][1]['target']['raw-content'][10]);
    }

    /**
     * @test
     */
    public function can_read_files_with_empty_target()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber/56d591a5-louvre-v2-en_us-fr_fr-PM.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEmpty($units[1]['target']['raw-content'][0]);
        $this->assertEmpty($units[1]['target']['raw-content'][1]);
    }

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
