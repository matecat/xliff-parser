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
    public function can_replace_a_xliff_20_with_mda_without_notes_or_original_data()
    {
        $data = $this->getData([
                [
                        'sid' => 1,
                        'segment' => 'Join our webinar: “<pc dataRefEnd="d2" dataRefStart="d1" id="1" subType="xlf:b" type="fmt">Machine Learning in Cyber Security: What It Is and What It Isn\'t</pc>”',
                        'internal_id' => 'u1',
                        'mrk_id' => '',
                        'prev_tags' => '',
                        'succ_tags' => '',
                        'mrk_prev_tags' => '',
                        'mrk_succ_tags' => '',
                        'translation' => 'Bla bla bla',
                        'status' => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count' => 1,
                        'raw_word_count' => 1,
                ],
        ]);

        $inputFile = __DIR__.'/../tests/files/xliff20-without-notes-or-original-data.xliff';
        $outputFile = __DIR__.'/../tests/files/output/xliff20-without-notes-or-original-data.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);

        $output = file_get_contents($outputFile);

        // check if there is only one <mda:metadata>
        $this->assertEquals(1, substr_count($output, '<mda:metadata>'));
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_with_mda_without_duplicate_it()
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => 'Join our webinar: “<pc dataRefEnd="d2" dataRefStart="d1" id="1" subType="xlf:b" type="fmt">Machine Learning in Cyber Security: What It Is and What It Isn\'t</pc>”',
                'internal_id' => 'u1-1',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
        ]);

        $inputFile = __DIR__.'/../tests/files/xliff-20-with-mda.xlf';
        $outputFile = __DIR__.'/../tests/files/output/xliff-20-with-mda.xlf';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);

        $output = file_get_contents($outputFile);

        // check if there is only one <mda:metadata>
        $this->assertEquals(1, substr_count($output, '<mda:metadata>'));
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_12_without_target()
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => 'Bla Bla',
                'internal_id' => 'NFDBB2FA9-tu519',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                'sid' => 2,
                'segment' => 'Bla Bla',
                'internal_id' => 'NFDBB2FA9-tu52',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                'sid' => 3,
                'segment' => 'Bla Bla',
                'internal_id' => 'NFDBB2FA9-tu523',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                'sid' => 4,
                'segment' => 'Bla Bla',
                'internal_id' => 'NFDBB2FA9-tu523',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                'sid' => 5,
                'segment' => 'Bla Bla',
                'internal_id' => 'NFDBB2FA9-tu522',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
        ]);

        $inputFile = __DIR__.'/../tests/files/file-with-nested-group-and-missing-target.xliff';
        $outputFile = __DIR__.'/../tests/files/output/file-with-nested-group-and-missing-target.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);
        $output = $xliffParser->xliffToArray(file_get_contents($outputFile));

        $expected = 'Bla bla bla';

        $this->assertEquals($output['files'][3]['trans-units'][1]['target']['raw-content'], $expected);
        $this->assertEquals($output['files'][3]['trans-units'][2]['target']['raw-content'], $expected);
        $this->assertEquals($output['files'][3]['trans-units'][3]['target']['raw-content'], $expected);
    }

    /**
     * @test
     */
    public function can_replace_an_intermediate_xliff_12_without_target()
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => 'Bla Bla',
                'internal_id' => 'NFDBB2FA9-tu1',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
        ]);

        $inputFile = __DIR__.'/../tests/files/intermediate_xliff.xliff';
        $outputFile = __DIR__.'/../tests/files/output/intermediate_xliff.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);
        $output = $xliffParser->xliffToArray(file_get_contents($outputFile));

        $file = $output['files'][3];
        $transUnit = $file['trans-units'][1];
        $segSource = $transUnit['seg-source'];
        $source = $transUnit['source'];
        $target = $transUnit['target'];

        $this->assertEquals($file['attr']['data-type'], 'x-undefined');
        $this->assertEquals($file['attr']['original'], 'word/document.xml');
        $this->assertEquals($file['attr']['source-language'], 'en-GB');
        $this->assertEquals($file['attr']['target-language'], 'sk-SK');
        $this->assertEquals($transUnit['attr']['id'], 'NFDBB2FA9-tu1');
        $this->assertEquals($segSource[0]['mid'], 0);
        $this->assertEquals($segSource[0]['raw-content'], 'The system for creative people is broken');
        $this->assertEquals($source['raw-content'], 'The system for creative people is broken');
        $this->assertEquals($source['attr']['xml:lang'], 'en-GB');
        $this->assertEquals($target['raw-content'], 'Bla bla bla');
        $this->assertEquals($target['attr']['xml:lang'], 'sk-SK');
        $this->assertEquals($target['attr']['state'], 'translated');
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_10_without_target_lang()
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => 'Image showing Italian Patreon creators',
                'internal_id' => 'pendo-image-e3aaf7b7|alt',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ]
        ]);

        $inputFile = __DIR__.'/../tests/files/no-target.xliff';
        $outputFile = __DIR__.'/../tests/files/output/no-target.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'it-it', $outputFile);
        $output = $xliffParser->xliffToArray(file_get_contents($outputFile));

        $this->assertEquals($output['files'][1]['attr']['target-language'], 'it-it');
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_10()
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => '<g id="1">&#128076;&#127995;</g>',
                'internal_id' => 'NFDBB2FA9-tu519',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => '<g id="1">&#128076;&#127995;</g>',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ]
        ]);

        $inputFile = __DIR__.'/../tests/files/file-with-emoji.xliff';
        $outputFile = __DIR__.'/../tests/files/output/file-with-emoji.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'fr-fr', $outputFile);
        $output = $xliffParser->xliffToArray(file_get_contents($outputFile));
        $expected = '<g id="1">&#128076;&#127995;</g>';

        $this->assertEquals($expected, $output['files'][3]['trans-units'][1]['target']['raw-content']);
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_without_target()
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => 'Titolo del documento',
                'internal_id' => 'tu1',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Document title',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 2,
            ],
            [
                'sid' => 2,
                'segment' => 'Titolo del documento2',
                'internal_id' => 'tu1',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Document title2',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 3,
                'raw_word_count' => 4,
            ],
            [
                'sid' => 3,
                'segment' => 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.',
                'internal_id' => 'tu2',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">cursive</pc>.',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 4,
                'raw_word_count' => 5,
            ],
        ]);

        $inputFile = __DIR__.'/../tests/files/1111_prova.md.xlf';
        $outputFile = __DIR__.'/../tests/files/output/1111_prova.md.xlf';

        (new XliffParser())->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'en-en', $outputFile, false);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));
        $expected = 'Document title';
        $expected2 = 'Document title2';
        $expected3 = 'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">cursive</pc>.';

        $unit1 = $output['files'][1]['trans-units'][1];
        $unit2 = $output['files'][1]['trans-units'][2];

        $this->assertEquals($unit1['attr']['id'], 'tu1');
        $this->assertEquals($unit2['attr']['id'], 'tu2');
        $this->assertEquals($unit1['source']['attr'][0]['xml:space'], 'preserve');
        $this->assertEquals($unit1['source']['attr'][1]['xml:space'], 'preserve');
        $this->assertEquals($unit2['source']['attr'][0]['xml:space'], 'preserve');
        $this->assertEquals($unit1['source']['raw-content'][0], 'Titolo del documento');
        $this->assertEquals($unit1['source']['raw-content'][1], 'Titolo del documento2');
        $this->assertEquals($unit2['source']['raw-content'][0], 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.');
        $this->assertEquals($unit1['seg-source'][0]['mid'], 0);
        $this->assertEquals($unit1['seg-source'][0]['raw-content'], 'Titolo del documento');
        $this->assertEquals($unit1['seg-source'][1]['mid'], 1);
        $this->assertEquals($unit1['seg-source'][1]['raw-content'], 'Titolo del documento2');
        $this->assertEquals($unit2['seg-source'][0]['mid'], 0);
        $this->assertEquals($unit2['seg-source'][0]['raw-content'], 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.');
        $this->assertNotEmpty($unit1['target']['raw-content']);
        $this->assertNotEmpty($unit1['target']['raw-content']);
        $this->assertNotEmpty($unit2['target']['raw-content']);
        $this->assertEquals($expected, $unit1['target']['raw-content'][0]);
        $this->assertEquals($expected2, $unit1['target']['raw-content'][1]);
        $this->assertEquals($expected3, $unit2['target']['raw-content'][0]);
        $this->assertNotEmpty($unit1['seg-target'][0]);
        $this->assertNotEmpty($unit1['seg-target'][1]);
        $this->assertNotEmpty($unit2['seg-target'][0]);
        $this->assertEquals($unit1['seg-target'][0]['mid'], 0);
        $this->assertEquals($unit1['seg-target'][0]['raw-content'], 'Document title');
        $this->assertEquals($unit1['seg-target'][1]['mid'], 1);
        $this->assertEquals($unit1['seg-target'][1]['raw-content'], 'Document title2');
        $this->assertEquals($unit2['seg-target'][0]['mid'], 0);
        $this->assertEquals($unit2['seg-target'][0]['raw-content'], 'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">cursive</pc>.');
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_with_no_errors()
    {
        $data = $this->getData([
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
        ]);
        $inputFile = __DIR__.'/../tests/files/sample-20.xlf';
        $outputFile = __DIR__.'/../tests/files/output/sample-20.xlf';

        (new XliffParser())->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'fr-fr', $outputFile, false, new DummyXliffReplacerCallback());
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));
        $expected = '<pc id="1">Buongiorno al <mrk id="m2" type="term">Mondo</mrk> !</pc>';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_with_consistency_errors()
    {
        $data = $this->getData([
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
        ]);
        $inputFile = __DIR__.'/../tests/files/sample-20.xlf';
        $outputFile = __DIR__.'/../tests/files/output/sample-20.xlf';

        (new XliffParser())->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'fr-fr', $outputFile, false, new DummyXliffReplacerCallbackWhichReturnTrue());
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));
        $expected = '|||UNTRANSLATED_CONTENT_START|||<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>|||UNTRANSLATED_CONTENT_END|||';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    /**
     * In this case the replacer must do not replace original target
     *
     * @test
     */
    public function can_replace_a_xliff_12_with__translate_no()
    {
        $data = $this->getData([
                [
                    'sid' => 1,
                    'segment' => 'Tools:Review',
                    'internal_id' => '1',
                    'mrk_id' => '',
                    'prev_tags' => '',
                    'succ_tags' => '',
                    'mrk_prev_tags' => '',
                    'mrk_succ_tags' => '',
                    'translation' => 'Tools:Recensione',
                    'status' => TranslationStatus::STATUS_TRANSLATED,
                    'eq_word_count' => 1,
                    'raw_word_count' => 1,
                ]
        ]);

        $inputFile = __DIR__.'/../tests/files/Working_with_the_Review_tool_single_tu.xlf';
        $outputFile = __DIR__.'/../tests/files/output/Working_with_the_Review_tool_single_tu.xlf';

        (new XliffParser())->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'it-it', $outputFile, false);
        $output = (new XliffParser())->xliffToArray(file_get_contents($outputFile));
        $expected = '<mrk mtype="seg" mid="1" MadCap:segmentStatus="Untranslated" MadCap:matchPercent="0"/>';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content']);
    }
}

class RealXliffReplacerCallback implements XliffReplacerCallbackInterface
{
    /**
     * @inheritDoc
     */
    public function thereAreErrors($segment, $translation, array $dataRefMap = [])
    {
        return false;
    }
}

class DummyXliffReplacerCallback implements XliffReplacerCallbackInterface
{
    /**
     * @inheritDoc
     */
    public function thereAreErrors($segment, $translation, array $dataRefMap = [])
    {
        return false;
    }
}

class DummyXliffReplacerCallbackWhichReturnTrue implements XliffReplacerCallbackInterface
{
    /**
     * @inheritDoc
     */
    public function thereAreErrors($segment, $translation, array $dataRefMap = [])
    {
        return true;
    }
}
