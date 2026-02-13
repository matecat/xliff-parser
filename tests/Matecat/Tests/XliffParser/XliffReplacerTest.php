<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Exception;
use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffReplacer\XliffReplacerCallbackInterface;
use Matecat\XliffParser\XliffReplacer\XliffReplacerFactory;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class XliffReplacerTest extends Base
{
    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_12_with_context_group(): void
    {
        $data = $this->getData([
            [
                "data_ref_map" => null,
                "eq_word_count" => "0.00",
                "error" => "",
                "internal_id" => "tu1",
                "mrk_id" => "0",
                "mrk_prev_tags" => null,
                "mrk_succ_tags" => null,
                "prev_tags" => "",
                "r2" => null,
                "raw_word_count" => "1.00",
                "segment" => "Confirm",
                "sid" => "119092",
                "source_page" => null,
                "status" => "TRANSLATED",
                "succ_tags" => "",
                "translation" => "يتأكد",
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/13578661#IFgFi8oGn6.xlf');
        $outputPath = 'output/13578661#IFgFi8oGn6.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'ar-JO', $outputFile);

        $output = $xliffParser->xliffToArray($this->getTestFile($outputPath));
        $this->assertEquals(
            'Confirm',
            $output['files'][3]['trans-units'][1]['seg-source'][0]['raw-content']
        );
        $this->assertEquals(
            "يتأكد",
            $output['files'][3]['trans-units'][1]['seg-target'][0]['raw-content']
        );

        $outputRawContent = $this->getTestFile($outputPath);
        $this->assertTrue(mb_strpos($outputRawContent, "يتأكد") > 0);
        $this->assertTrue(mb_strpos($outputRawContent, '</xliff>') > 0);
        $this->assertTrue(mb_strpos($outputRawContent, '</file>') > 0);
        $this->assertTrue(mb_strpos($outputRawContent, '</body>') > 0);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_20_without_trgLang_attribute(): void
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => 'Deutsch',
                'internal_id' => 'I11:359;122:3567',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Alemán',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
        ]);

        $inputFile = $this->getTestFilePath('20/no-trgLang.xliff');
        $outputPath = 'output/no-trgLang.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'es-ES', $outputFile);

        $output = $xliffParser->xliffToArray($this->getTestFile($outputPath));

        $this->assertEquals('es-ES', $output['files'][1]['attr']['target-language']);
    }

    #[Test]
    public function can_replace_a_xliff_20_with_the_correct_counts(): void
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => 'bla bla bla',
                'internal_id' => '0',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 20,
                'raw_word_count' => 30,
            ],
            [
                'sid' => 2,
                'segment' => 'bla bla bla',
                'internal_id' => '1',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Bla bla bla',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 40,
                'raw_word_count' => 60,
            ],
        ]);

        $inputFile = $this->getTestFilePath('20/uber/uber-counts.xliff');
        $outputPath = '20/uber/output/uber-counts.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);

        $output = $this->getTestFile($outputPath);

        preg_match_all('/<mda:meta type="x-matecat-raw">(.*)<\/mda:meta>/', $output, $raw);
        preg_match_all('/<mda:meta type="x-matecat-weighted">(.*)<\/mda:meta>/', $output, $weighted);

        $this->assertEquals(30, $raw[1][0]);
        $this->assertEquals(60, $raw[1][1]);
        $this->assertEquals(20, $weighted[1][0]);
        $this->assertEquals(40, $weighted[1][1]);

        // check for metaGroup attributes
        preg_match_all('/<mda:metaGroup id="(.*)" category="(.*)">/', $output, $metaGroup);

        $this->assertEquals('word_count_tu.0.0', $metaGroup[1][0]);
        $this->assertEquals('word_count_tu.1.0', $metaGroup[1][1]);
    }

    #[Test]
    public function can_replace_a_xliff_20_with_mda_without_notes_or_original_data(): void
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

        $inputFile = $this->getTestFilePath('20/xliff20-without-notes-or-original-data.xliff');
        $outputPath = 'output/xliff20-without-notes-or-original-data.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);

        $output = $this->getTestFile($outputPath);

        // check if there is only one <mda:metadata>
        $this->assertEquals(1, substr_count($output, '<mda:metadata>'));
    }

    #[Test]
    public function validate_xliff_20_without_notes_or_original_data(): void
    {
        $outputPath = 'output/xliff20-without-notes-or-original-data.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        try {
            $validate = $this->validateXliff20($outputFile);
            $this->assertEmpty($validate);
        } catch (Exception $exception) {
            $this->markTestSkipped('The xliff validation service is out of order. ' . $exception->getMessage());
        }
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_20_with_mda_without_duplicate_it(): void
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

        $inputFile = $this->getTestFilePath('20/xliff-20-with-mda.xlf');
        $outputPath = 'output/xliff-20-with-mda.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);

        // validate XML
        $xliffParser->xliffToArray($this->getTestFile($outputPath));

        // check if there is only one <mda:metadata>
        $this->assertEquals(1, substr_count($this->getTestFile($outputPath), '<mda:metadata>'));
    }

    #[Test]
    public function validate_xliff_20_with_mda_prefilled(): void
    {
        $outputPath = 'output/xliff-20-with-mda.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        try {
            $validate = $this->validateXliff20($outputFile);
            $this->assertEmpty($validate);
        } catch (Exception $exception) {
            $this->markTestSkipped('The xliff validation service is out of order. ' . $exception->getMessage());
        }
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_12_without_target(): void
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
                'internal_id' => 'NFDBB2FA9-tu524',
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
                'internal_id' => 'NFDBB2FA9-tu525',
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

        $inputFile = $this->getTestFilePath('12/file-with-nested-group-and-missing-target.xliff');
        $outputPath = 'output/file-with-nested-group-and-missing-target.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);
        $output = $xliffParser->xliffToArray($this->getTestFile($outputPath));

        $expected = 'Bla bla bla';

        $this->assertEquals($output['files'][3]['trans-units'][1]['target']['raw-content'], $expected);
        $this->assertEquals($output['files'][3]['trans-units'][2]['target']['raw-content'], $expected);
        $this->assertEquals($output['files'][3]['trans-units'][3]['target']['raw-content'], $expected);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_an_intermediate_xliff_12_without_target(): void
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

        $inputFile = $this->getTestFilePath('12/intermediate_xliff.xliff');
        $outputPath = 'output/intermediate_xliff.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'sk-SK', $outputFile);
        $output = $xliffParser->xliffToArray($this->getTestFile($outputPath));

        $file = $output['files'][3];
        $transUnit = $file['trans-units'][1];
        $segSource = $transUnit['seg-source'];
        $source = $transUnit['source'];
        $target = $transUnit['target'];

        $this->assertEquals('x-undefined', $file['attr']['data-type']);
        $this->assertEquals('word/document.xml', $file['attr']['original']);
        $this->assertEquals('en-GB', $file['attr']['source-language']);
        $this->assertEquals('sk-SK', $file['attr']['target-language']);
        $this->assertEquals('NFDBB2FA9-tu1', $transUnit['attr']['id']);
        $this->assertEquals(0, $segSource[0]['mid']);
        $this->assertEquals('The system for creative people is broken', $segSource[0]['raw-content']);
        $this->assertEquals('The system for creative people is broken', $source['raw-content']);
        $this->assertEquals('en-GB', $source['attr']['xml:lang']);
        $this->assertEquals('Bla bla bla', $target['raw-content']);
        $this->assertEquals('sk-SK', $target['attr']['xml:lang']);
        $this->assertEquals('translated', $target['attr']['state']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_10_without_target_lang(): void
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

        $inputFile = $this->getTestFilePath('12/no-target.xliff');
        $outputPath = 'output/no-target.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'it-it', $outputFile);
        $output = $xliffParser->xliffToArray($this->getTestFile($outputPath));

        $this->assertEquals('it-it', $output['files'][1]['attr']['target-language']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function should_replace_a_translation_with_0_as_string(): void
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
                'translation' => '0', // <----
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ]
        ]);

        $inputFile = $this->getTestFilePath('12/no-target.xliff');
        $outputPath = 'output/no-target.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'it-it', $outputFile);
        $output = $xliffParser->xliffToArray($this->getTestFile($outputPath));

        $this->assertEquals('0', $output['files'][1]['trans-units'][1]['target']['raw-content']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_10(): void
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

        $inputFile = $this->getTestFilePath('12/file-with-emoji.xliff');
        $outputPath = 'output/file-with-emoji.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'fr-fr', $outputFile);
        $output = $xliffParser->xliffToArray($this->getTestFile($outputPath));
        $expected = '<g id="1">&#128076;&#127995;</g>';

        $this->assertEquals($expected, $output['files'][3]['trans-units'][1]['target']['raw-content']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_20_without_target(): void
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

        $inputFile = $this->getTestFilePath('20/1111_prova.md.xlf');
        $outputPath = 'output/1111_prova.md.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation($inputFile, $data['data'], $data['transUnits'], 'en-en', $outputFile);
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));
        $expected = 'Document title';
        $expected2 = 'Document title2';
        $expected3 = 'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">cursive</pc>.';

        $unit1 = $output['files'][1]['trans-units'][1];
        $unit2 = $output['files'][1]['trans-units'][2];

        $this->assertEquals('tu1', $unit1['attr']['id']);
        $this->assertEquals('tu2', $unit2['attr']['id']);
        $this->assertEquals('preserve', $unit1['source']['attr'][0]['xml:space']);
        $this->assertEquals('preserve', $unit1['source']['attr'][1]['xml:space']);
        $this->assertEquals('preserve', $unit2['source']['attr'][0]['xml:space']);
        $this->assertEquals('Titolo del documento', $unit1['source']['raw-content'][0]);
        $this->assertEquals('Titolo del documento2', $unit1['source']['raw-content'][1]);
        $this->assertEquals(
            'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.',
            $unit2['source']['raw-content'][0]
        );
        $this->assertEquals(0, $unit1['seg-source'][0]['mid']);
        $this->assertEquals('Titolo del documento', $unit1['seg-source'][0]['raw-content']);
        $this->assertEquals(1, $unit1['seg-source'][1]['mid']);
        $this->assertEquals('Titolo del documento2', $unit1['seg-source'][1]['raw-content']);
        $this->assertEquals(0, $unit2['seg-source'][0]['mid']);
        $this->assertEquals(
            'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.',
            $unit2['seg-source'][0]['raw-content']
        );
        $this->assertNotEmpty($unit1['target']['raw-content']);
        $this->assertNotEmpty($unit1['target']['raw-content']);
        $this->assertNotEmpty($unit2['target']['raw-content']);
        $this->assertEquals($expected, $unit1['target']['raw-content'][0]);
        $this->assertEquals($expected2, $unit1['target']['raw-content'][1]);
        $this->assertEquals($expected3, $unit2['target']['raw-content'][0]);
        $this->assertNotEmpty($unit1['seg-target'][0]);
        $this->assertNotEmpty($unit1['seg-target'][1]);
        $this->assertNotEmpty($unit2['seg-target'][0]);
        $this->assertEquals(0, $unit1['seg-target'][0]['mid']);
        $this->assertEquals('Document title', $unit1['seg-target'][0]['raw-content']);
        $this->assertEquals(1, $unit1['seg-target'][1]['mid']);
        $this->assertEquals('Document title2', $unit1['seg-target'][1]['raw-content']);
        $this->assertEquals(0, $unit2['seg-target'][0]['mid']);
        $this->assertEquals(
            'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">cursive</pc>.',
            $unit2['seg-target'][0]['raw-content']
        );

        // check counters
        preg_match_all(
            '/<mda:meta type="x-matecat-raw">(.*?)<\/mda:meta>/s',
            $this->getTestFile($outputPath),
            $rawWords
        );
        preg_match_all(
            '/<mda:meta type="x-matecat-weighted">(.*?)<\/mda:meta>/s',
            $this->getTestFile($outputPath),
            $weightedWords
        );

        $this->assertEquals(5, $rawWords[1][1]);
        $this->assertEquals(4, $weightedWords[1][1]);
    }

    #[Test]
    public function invalid_target_language(): void
    {
        $outputPath = 'output/1111_prova.md.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        try {
            $validate = $this->validateXliff20($outputFile);
            $this->assertNotEmpty($validate);
        } catch (Exception $exception) {
            $this->markTestSkipped('The xliff validation service is out of order. ' . $exception->getMessage());
        }
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_20_with_no_errors(): void
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
                'r2' => null,
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
                'r2' => null,
                'eq_word_count' => 200,
                'raw_word_count' => 300,
            ],
        ]);
        $inputFile = $this->getTestFilePath('20/sample-20.xlf');
        $outputPath = 'output/sample-20.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'fr-fr',
            $outputFile
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));
        $expected = '<pc id="1">Buongiorno al <mrk id="m2" type="term">Mondo</mrk> !</pc>';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    #[Test]
    public function validate_sample_xliff_20(): void
    {
        $outputPath = 'output/sample-20.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        try {
            $validate = $this->validateXliff20($outputFile);
            $this->assertEmpty($validate);
        } catch (Exception $exception) {
            $this->markTestSkipped('The xliff validation service is out of order. ' . $exception->getMessage());
        }
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_20_with_consistency_errors(): void
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
                'r2' => null,
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
                'r2' => null,
                'eq_word_count' => 200,
                'raw_word_count' => 300,
            ],
        ]);
        $inputFile = $this->getTestFilePath('20/sample-20.xlf');
        $outputPath = 'output/sample-20.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'fr-fr',
            $outputFile,
            false,
            new DummyXliffReplacerCallbackWhichReturnTrue()
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));
        $expected = '|||UNTRANSLATED_CONTENT_START|||<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>|||UNTRANSLATED_CONTENT_END|||';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function xliff20_should_not_overwrite_translation_candidates_with_consistency_errors(): void
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
                'r2' => null,
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
                'r2' => null,
                'eq_word_count' => 200,
                'raw_word_count' => 300,
            ],
        ]);
        $inputFile = $this->getTestFilePath('20/valid_sample-20-translation-candidates.xlf');
        $outputPath = 'output/valid_sample-20-translation-candidates.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'fr-fr',
            $outputFile,
            false,
            new DummyXliffReplacerCallbackWhichReturnTrue()
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));
        $expected = '|||UNTRANSLATED_CONTENT_START|||<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>|||UNTRANSLATED_CONTENT_END|||';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);

        $content = $this->getTestFile($outputPath);
        $this->assertTrue((bool)preg_match('#<target>Il est mon ami.</target>#', $content));
        $this->assertTrue(str_contains($content, 'Il est mon meilleur ami'));
    }


    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_12_with_consistency_errors(): void
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => '<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>',
                'internal_id' => '0000000121',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Hola mundo!',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 2,
            ],
        ]);
        $inputFile = $this->getTestFilePath('12/file-with-self-closed-tag-and-alt-trans.xliff');
        $outputPath = 'output/_file-with-self-closed-tag-and-alt-trans.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'fr-fr',
            $outputFile,
            false,
            new DummyXliffReplacerCallbackWhichReturnTrue()
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));
        $expected = '|||UNTRANSLATED_CONTENT_START|||<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>|||UNTRANSLATED_CONTENT_END|||';

        $this->assertEquals($expected, $output['files'][3]['trans-units'][1]['target']['raw-content']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_12_with__translate_no(): void
    {
        $data = $this->getData([]);

        $inputFile = $this->getTestFilePath('12/Working_with_the_Review_tool_single_tu.xlf');
        $outputPath = 'output/Working_with_the_Review_tool_single_tu.xlf';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'it-it',
            $outputFile
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));
        $expected = '<mrk mtype="seg" mid="1" MadCap:segmentStatus="Untranslated" MadCap:matchPercent="0"/>';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_replace_a_xliff_12_with_mrk_and_g(): void
    {
        $data = $this->getData([
            [
                'sid' => 1,
                'segment' => '<g id="1"><mrk mid="0" mtype="seg">An English string with g tags</mrk></g>',
                'internal_id' => '251971551065',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => '<g id="1"><mrk mid="0" mtype="seg">Paperone</mrk></g>',
                'status' => TranslationStatus::STATUS_APPROVED,
                'r2' => 1,
                'eq_word_count' => 3,
                'raw_word_count' => 6,
            ],
            [
                'sid' => 2,
                'segment' => '<mrk mid="0" mtype="seg">This unit has a comment too</mrk>',
                'internal_id' => '251971551066',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => '<mrk mid="0" mtype="seg">Paperino</mrk>',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'r2' => 1,
                'eq_word_count' => 3,
                'raw_word_count' => 6,
            ],
            [
                'sid' => 3,
                'segment' => '<mrk mid="0" mtype="seg">Source</mrk>',
                'internal_id' => '251971551068',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'Sorgente',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                'sid' => 4,
                'segment' => '<mrk mid="1" mtype="seg">of</mrk>',
                'internal_id' => '251971551068',
                'mrk_id' => '1',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'di',
                'status' => TranslationStatus::STATUS_APPROVED2,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                'sid' => 5,
                'segment' => '<mrk mid="2" mtype="seg">truth</mrk>',
                'internal_id' => '251971551068',
                'mrk_id' => '2',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => 'verità',
                'status' => TranslationStatus::STATUS_APPROVED,
                'eq_word_count' => 1,
                'raw_word_count' => 1,
            ],
            [
                'sid' => 6,
                'segment' => '<mrk mid="0" mtype="seg">An English string</mrk>',
                'internal_id' => '251971551067',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => '<g id="1"><g id="2"><mrk mid="0" mtype="seg"><ex id="1">Paperoga</ex></mrk></g></g>',
                'status' => TranslationStatus::STATUS_TRANSLATED,
                'r2' => 1,
                'eq_word_count' => 2,
                'raw_word_count' => 3,
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/file-with-notes-and-no-target-seg-source-with-external-g-tag.xliff');
        $outputPath = 'output/file-with-notes-and-no-target-seg-source-with-external-g-tag.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'it-it',
            $outputFile
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));

        $expected = '<g id="1"><mrk mid="0" mtype="seg">Paperone</mrk></g>';
        $this->assertEquals($expected, $output['files'][3]['trans-units'][1]['target']['raw-content']);

        $expected = '<mrk mid="0" mtype="seg">Paperino</mrk>';
        $this->assertEquals($expected, $output['files'][3]['trans-units'][2]['target']['raw-content']);

        $expected = '<mrk mid="0" mtype="seg">Sorgente</mrk><mrk mid="1" mtype="seg">di</mrk><mrk mid="2" mtype="seg">verità</mrk>';
        $this->assertEquals($expected, $output['files'][3]['trans-units'][3]['target']['raw-content']);

        $expected = '<g id="1"><g id="2"><mrk mid="0" mtype="seg"><ex id="1">Paperoga</ex></mrk></g></g>';
        $this->assertEquals($expected, $output['files'][3]['trans-units'][4]['target']['raw-content']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function should_replace_empty_12_units(): void
    {
        $data = $this->getData([
            [

                'data_ref_map' => null,
                'eq_word_count' => null,
                'error' => null,
                'internal_id' => "P3953B09A-tu1",
                'mrk_id' => null,
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'prev_tags' => null,
                'r2' => null,
                'raw_word_count' => "0.00",
                'segment' => "",
                'sid' => "0",
                'source_page' => null,
                'status' => null,
                'succ_tags' => null,
                'translation' => null,

            ],
            [

                'data_ref_map' => null,
                'eq_word_count' => null,
                'error' => null,
                'internal_id' => "P3953B09A-tu2",
                'mrk_id' => null,
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'prev_tags' => null,
                'r2' => null,
                'raw_word_count' => "0.00",
                'segment' => "",
                'sid' => "1",
                'source_page' => null,
                'status' => null,
                'succ_tags' => null,
                'translation' => null,

            ],
            [
                'data_ref_map' => null,
                'eq_word_count' => "0.30",
                'error' => null,
                'internal_id' => "P6D3F672D-sub1",
                'mrk_id' => "0",
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'prev_tags' => "",
                'r2' => null,
                'raw_word_count' => "1.00",
                'segment' => "ARISTON",
                'sid' => "2",
                'source_page' => null,
                'status' => "NEW",
                'succ_tags' => "",
                'translation' => "ARISTON",
            ]
        ]);

        $inputFile = $this->getTestFilePath('12/test-empty-unit-1.2.xliff');
        $outputPath = 'output/test-empty-unit-1.2.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'it-it',
            $outputFile
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));

        $expected = '';
        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content']);
        $this->assertEquals($expected, $output['files'][1]['trans-units'][2]['target']['raw-content']);

        $expected = '<mrk mid="0" mtype="seg">ARISTON</mrk>';
        $this->assertEquals($expected, $output['files'][1]['trans-units'][3]['target']['raw-content']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function should_replace_20_units_with_notes_after_segment(): void
    {
        $data = $this->getData([
            [

                'data_ref_map' => null,
                'eq_word_count' => null,
                'error' => null,
                'internal_id' => "c5c30ef1-1fe7-434a-afb3-4c5035025b40",
                'mrk_id' => null,
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'prev_tags' => null,
                'r2' => null,
                'raw_word_count' => "3.00",
                'segment' => "",
                'sid' => "0",
                'source_page' => null,
                'status' => "NEW",
                'succ_tags' => null,
                'translation' => "Bevi Coca Cola!",

            ],
        ]);

        $inputFile = $this->getTestFilePath('20/notes-after-segment.xliff');
        $outputPath = 'output/notes-after-segment.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'it-it',
            $outputFile
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));

        $expected = 'Bevi Coca Cola!';
        $expectedNote1 = '3.00';
        $expectedNote2 = '0';
        $expectedNote3 = '###___EMPTY_TAG_PLACEHOLDER___###';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
        $this->assertEquals($expectedNote1, $output['files'][1]['trans-units'][1]['notes'][0]['raw-content']);
        $this->assertEquals($expectedNote2, $output['files'][1]['trans-units'][1]['notes'][1]['raw-content']);
        $this->assertEquals($expectedNote3, $output['files'][1]['trans-units'][1]['notes'][2]['raw-content']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function should_replace_empty_20_units(): void
    {
        $data = $this->getData([
            [

                'data_ref_map' => null,
                'eq_word_count' => null,
                'error' => null,
                'internal_id' => "P3953B09A-tu1",
                'mrk_id' => null,
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'prev_tags' => null,
                'r2' => null,
                'raw_word_count' => "0.00",
                'segment' => "",
                'sid' => "0",
                'source_page' => null,
                'status' => null,
                'succ_tags' => null,
                'translation' => null,

            ],
            [

                'data_ref_map' => null,
                'eq_word_count' => null,
                'error' => null,
                'internal_id' => "P3953B09A-tu2",
                'mrk_id' => null,
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'prev_tags' => null,
                'r2' => null,
                'raw_word_count' => "0.00",
                'segment' => "",
                'sid' => "1",
                'source_page' => null,
                'status' => null,
                'succ_tags' => null,
                'translation' => null,

            ],
            [
                'data_ref_map' => null,
                'eq_word_count' => "0.30",
                'error' => null,
                'internal_id' => "P6D3F672D-sub1",
                'mrk_id' => "0",
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'prev_tags' => "",
                'r2' => null,
                'raw_word_count' => "1.00",
                'segment' => "ARISTON",
                'sid' => "2",
                'source_page' => null,
                'status' => "NEW",
                'succ_tags' => "",
                'translation' => "ARISTON",
            ]
        ]);

        $inputFile = $this->getTestFilePath('20/test-empty-unit-2.0.xliff');
        $outputPath = 'output/test-empty-unit-2.0.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'it-it',
            $outputFile
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));

        $expected = '';
        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content'][0]);
        $this->assertEquals($expected, $output['files'][1]['trans-units'][2]['target']['raw-content'][0]);

        $expected = 'ARISTON';
        $this->assertEquals($expected, $output['files'][1]['trans-units'][3]['target']['raw-content'][0]);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function should_replace_12_units_with_empty_segments_with_the_correct_state(): void
    {
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'Yahoo Creators',
                'internal_id' => '2973331',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Creadores de Yahoo',
                'status' => 'APPROVED',
                'error' => '',
                'eq_word_count' => '1.34',
                'raw_word_count' => '2.00',
                'source_page' => null,
                'r2' => null,
                'data_ref_map' => null,
            ],
            [
                'sid' => '2',
                'segment' => ' ',
                'internal_id' => '2973421',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => '',
                'mrk_succ_tags' => '',
                'translation' => null,
                'status' => null,
                'error' => null,
                'eq_word_count' => null,
                'raw_word_count' => '0.00',
                'source_page' => null,
                'r2' => null,
                'data_ref_map' => null,
            ],
            [
                'sid' => '3',
                'segment' => 'and <x id="1"/>',
                'internal_id' => '2973421',
                'mrk_id' => '1',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'et <x id="1"/>',
                'status' => 'APPROVED',
                'error' => '',
                'eq_word_count' => '0.00',
                'raw_word_count' => '1.00',
                'source_page' => null,
                'r2' => null,
                'data_ref_map' => null,
            ],
            [
                'sid' => '4',
                'segment' => 'This morning\'s news digest',
                'internal_id' => '2973422',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Resumen de noticias de esta mañana',
                'status' => 'APPROVED',
                'error' => '',
                'eq_word_count' => '2.68',
                'raw_word_count' => '4.00',
                'source_page' => null,
                'r2' => null,
                'data_ref_map' => null,
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/empty-mrk.xliff');
        $outputPath = 'output/empty-mrk.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'it-it',
            $outputFile
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));

        $status = 'signed-off';
        $this->assertEquals($status, $output['files'][1]['trans-units'][1]['target']['attr']['state']);
        $this->assertEquals($status, $output['files'][1]['trans-units'][2]['target']['attr']['state']);
        $this->assertEquals($status, $output['files'][1]['trans-units'][3]['target']['attr']['state']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function should_replace_12_units_with_entities(): void
    {
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'Hello&apos;&apos; ',
                'internal_id' => '2973331',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Ciao&apos;&apos; ',
                'status' => 'APPROVED',
                'error' => '',
                'eq_word_count' => '1.34',
                'raw_word_count' => '2.00',
                'source_page' => null,
                'r2' => null,
                'data_ref_map' => null,
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/with-entities.xliff');
        $outputPath = 'output/with-entities.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'it-it',
            $outputFile
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));

        $this->assertEquals(
            "<mrk mid=\"0\" mtype=\"seg\">Ciao''</mrk> ",
            $output['files'][1]['trans-units'][1]['target']['raw-content']
        );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function other_tests_replacing_12_units_with_entities(): void
    {
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'Hello&apos;&apos; ',
                'internal_id' => '3142672',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'با دقت به کد زیر نگاه کنید. با کلیک روی «اجرا»، این برنامه کدام طراحی را انجام می‌دهد؟',
                'status' => 'APPROVED',
                'error' => '',
                'eq_word_count' => '1.34',
                'raw_word_count' => '2.00',
                'source_page' => null,
                'r2' => null,
                'data_ref_map' => null,
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/entities.xliff');
        $outputPath = 'output/entities.xliff';
        $outputFile = $this->getTestFilePath($outputPath);

        (new XliffParser())->replaceTranslation(
            $inputFile,
            $data['data'],
            $data['transUnits'],
            'it-it',
            $outputFile
        );
        $output = (new XliffParser())->xliffToArray($this->getTestFile($outputPath));

        $expected = '&lt;table&gt;
&lt;tr&gt;&lt;td&gt;A&lt;/td&gt;&lt;td&gt;&lt;img src="https://images.code.org/cfc3f8206438a60afe3be9afe7fc0a22-image-1489118742610.10.15.png" width="100px" style="mix-blend-mode: multiply;"/&gt;&lt;/td&gt;&lt;td&gt;&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;&lt;/td&gt;&lt;td&gt;B&lt;/td&gt;&lt;td&gt;&lt;img src="https://images.code.org/975b027684d2f5411b960bf82987663e-image-1489119999013.11.13.png" width="100px" style="mix-blend-mode: multiply;"/&gt;&lt;/td&gt;&lt;td&gt;&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;&lt;/td&gt;&lt;td&gt;C&lt;/td&gt;&lt;td&gt;&lt;img src="https://images.code.org/635ac54ed7cb2e2d24eb341b3ec4eecb-image-1489120024059.12.00.png" width="80px" style="mix-blend-mode: multiply; clip: rect(0px,0px,0px,40px);"/&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;/table&gt;

&lt;br/&gt;&lt;br/&gt;

';

        $this->assertEquals($expected, $output['files'][1]['trans-units'][1]['target']['raw-content']);
    }

    /**
     * @covers AbstractXliffReplacer::runParser RuntimeException
     * @return void
     */
    #[Test]
    public function replace_translation_throws_exception_for_invalid_xml(): void
    {
        // Create a temporary file with invalid XML
        $tempInputFile = sys_get_temp_dir() . '/invalid_xliff_' . uniqid() . '.xliff';
        $invalidXml = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
<file source-language="en" target-language="it" datatype="plaintext" original="test.txt">
<body>
<trans-unit id="1">
<source>Hello</source>
<target>Ciao</target>
<!-- Missing closing tags - invalid XML -->';
        file_put_contents($tempInputFile, $invalidXml);

        $outputFile = sys_get_temp_dir() . '/output_invalid_' . uniqid() . '.xliff';

        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'Hello',
                'internal_id' => '1',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Ciao',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '1.00',
                'raw_word_count' => '1.00',
            ],
        ]);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('XML error:');

            // Use factory directly to bypass XliffParser's exception handling
            $replacer = XliffReplacerFactory::getInstance(
                $tempInputFile,
                $data['data'],
                $data['transUnits'],
                'it-it',
                $outputFile,
                false
            );
            $replacer->replaceTranslation();
        } finally {
            @unlink($tempInputFile);
            @unlink($outputFile);
        }
    }

    /**
     * @covers AbstractXliffReplacer::createOutputFileIfDoesNotExist
     * @return void
     */
    #[Test]
    public function replace_translation_creates_output_file_if_not_exists(): void
    {
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'Hello',
                'internal_id' => '3142672',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Ciao',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '1.00',
                'raw_word_count' => '1.00',
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/entities.xliff');
        // Create a new output file path that doesn't exist
        $outputFile = sys_get_temp_dir() . '/new_output_' . uniqid() . '.xliff';

        // Ensure file doesn't exist
        @unlink($outputFile);
        $this->assertFileDoesNotExist($outputFile);

        try {
            // Use factory directly
            $replacer = XliffReplacerFactory::getInstance(
                $inputFile,
                $data['data'],
                $data['transUnits'],
                'it-it',
                $outputFile,
                false
            );
            $replacer->replaceTranslation();

            // File should now exist
            $this->assertFileExists($outputFile);
        } finally {
            @unlink($outputFile);
        }
    }

    /**
     * @covers AbstractXliffReplacer::setFileDescriptors RuntimeException
     * @return void
     */
    #[Test]
    public function replace_translation_throws_exception_for_unreadable_input_file(): void
    {
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'Hello',
                'internal_id' => '1',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Ciao',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '1.00',
                'raw_word_count' => '1.00',
            ],
        ]);

        // Create a temporary file and make it unreadable
        $tempInputFile = sys_get_temp_dir() . '/unreadable_' . uniqid() . '.xliff';
        file_put_contents(
            $tempInputFile,
            '<?xml version="1.0"?><xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2"><file/></xliff>'
        );

        $outputFile = sys_get_temp_dir() . '/output_' . uniqid() . '.xliff';

        // Make file unreadable BEFORE trying to access it
        chmod($tempInputFile, 0000);

        $exceptionThrown = false;
        $exceptionMessage = '';

        try {
            XliffReplacerFactory::getInstance(
                $tempInputFile,
                $data['data'],
                $data['transUnits'],
                'it-it',
                $outputFile,
                false
            );
        } catch (RuntimeException $e) {
            $exceptionThrown = true;
            $exceptionMessage = $e->getMessage();
        } finally {
            chmod($tempInputFile, 0644);
            @unlink($tempInputFile);
            @unlink($outputFile);
        }

        $this->assertTrue($exceptionThrown, 'Expected RuntimeException to be thrown');
        $this->assertStringContainsString('could not open XML input', $exceptionMessage);
    }

    /**
     * @covers AbstractXliffReplacer::setFileDescriptors RuntimeException
     * @return void
     */
    #[Test]
    public function replace_translation_throws_exception_for_unwritable_output_path(): void
    {
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'Hello',
                'internal_id' => '3142672',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Ciao',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '1.00',
                'raw_word_count' => '1.00',
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/entities.xliff');
        // Create an output path in a non-existent directory
        $outputFile = '/non_existent_directory_' . uniqid() . '/output.xliff';

        $exceptionThrown = false;
        $exceptionMessage = '';

        try {
            XliffReplacerFactory::getInstance(
                $inputFile,
                $data['data'],
                $data['transUnits'],
                'it-it',
                $outputFile,
                false
            );
        } catch (RuntimeException $e) {
            $exceptionThrown = true;
            $exceptionMessage = $e->getMessage();
        }

        $this->assertTrue($exceptionThrown, 'Expected RuntimeException to be thrown');
        $this->assertStringContainsString('could not open output file', $exceptionMessage);
    }

    /**
     * @covers AbstractXliffReplacer::replaceTranslation entities management
     * @return void
     */
    #[Test]
    public function replace_translation_handles_entity_at_buffer_boundary(): void
    {
        // This test covers lines 141-151 in AbstractXliffReplacer
        // The while loop triggers when an entity is INCOMPLETE at the buffer boundary
        // e.g., buffer ends with "&am" instead of "&amp;" - the regex won't match incomplete entity
        // so the & remains, and strlen(substr(buffer, ampPos)) <= 9

        $header = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
<file source-language="en" target-language="it" datatype="plaintext" original="test.txt">
<body>
<trans-unit id="1">
<source>';

        // Calculate padding to put & at position 4094 so "&am" ends exactly at 4096
        // and "p;test" is in the next buffer
        $headerLen = strlen($header);
        $targetAmpPos = 4094; // Position for &, so "&am" (3 chars) ends at 4096
        $paddingLen = $targetAmpPos - $headerLen;
        $padding = str_repeat('X', $paddingLen);

        $xliffContent = $header . $padding . '&amp;test</source>
<target/>
</trans-unit>
</body>
</file>
</xliff>';

        $tempInputFile = sys_get_temp_dir() . '/entity_boundary_' . uniqid() . '.xliff';
        file_put_contents($tempInputFile, $xliffContent);

        $outputFile = sys_get_temp_dir() . '/entity_boundary_output_' . uniqid() . '.xliff';

        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => $padding . '&amp;test',
                'internal_id' => '1',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'translated',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '1.00',
                'raw_word_count' => '1.00',
            ],
        ]);

        try {
            $replacer = XliffReplacerFactory::getInstance(
                $tempInputFile,
                $data['data'],
                $data['transUnits'],
                'it-it',
                $outputFile,
                false
            );
            $replacer->replaceTranslation();

            // Verify the output file exists and contains expected content
            $this->assertFileExists($outputFile);
            $outputContent = (string)file_get_contents($outputFile);
            $this->assertStringContainsString('translated', $outputContent);
        } finally {
            @unlink($tempInputFile);
            @unlink($outputFile);
        }
    }

    /**
     * @covers \Matecat\XliffParser\XliffReplacer\Xliff12
     *
     * Test Xliff12 with sourceInTarget=true (covers lines 277-278)
     */
    #[Test]
    public function xliff12_replace_translation_with_source_in_target(): void
    {
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'An English string',
                'internal_id' => '251971551065',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Une chaîne en anglais',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '3.00',
                'raw_word_count' => '3.00',
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/file-with-notes-converted-nobase64.xliff');
        $outputFile = sys_get_temp_dir() . '/source_in_target_' . uniqid() . '.xliff';

        try {
            // Use factory directly with sourceInTarget=true
            $replacer = XliffReplacerFactory::getInstance(
                $inputFile,
                $data['data'],
                $data['transUnits'],
                'fr-fr',
                $outputFile,
                true // sourceInTarget = true
            );
            $replacer->replaceTranslation();

            // Verify the output file exists
            $this->assertFileExists($outputFile);

            // When sourceInTarget is true, the target should contain the source content (not translation)
            $outputContent = (string)file_get_contents($outputFile);
            $this->assertStringContainsString('<target', $outputContent);
            // Source content should be in target, not the translation
            $this->assertStringContainsString('An English string', $outputContent);
        } finally {
            @unlink($outputFile);
        }
    }

    /**
     * @covers \Matecat\XliffParser\XliffReplacer\Xliff12
     *
     * Test Xliff12 with multiple segments having the same internal_id (covers line 266 - break)
     */
    #[Test]
    public function xliff12_replace_translation_with_multiple_mrk_segments(): void
    {
        // This test uses empty-mrk.xliff which has trans-unit 2973421 with multiple mrk tags (mid=0 and mid=1)
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => ' ',
                'internal_id' => '2973421',
                'mrk_id' => '0',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => ' ',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '0.00',
                'raw_word_count' => '1.00',
            ],
            [
                'sid' => '2',
                'segment' => 'and {condition}',
                'internal_id' => '2973421',
                'mrk_id' => '1',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'et {condition}',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '2.00',
                'raw_word_count' => '2.00',
            ],
            // Add a third segment with the same internal_id but lower mrk_id to trigger the break
            [
                'sid' => '3',
                'segment' => 'extra',
                'internal_id' => '2973421',
                'mrk_id' => '0', // This mrk_id <= lastMrkId (1), so it should trigger break
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'extra translated',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '1.00',
                'raw_word_count' => '1.00',
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/empty-mrk.xliff');
        $outputFile = sys_get_temp_dir() . '/multi_mrk_' . uniqid() . '.xliff';

        try {
            $replacer = XliffReplacerFactory::getInstance(
                $inputFile,
                $data['data'],
                $data['transUnits'],
                'fr-FR',
                $outputFile,
                false
            );
            $replacer->replaceTranslation();

            $this->assertFileExists($outputFile);
            $outputContent = (string)file_get_contents($outputFile);
            // Verify the file was processed (contains target tags)
            $this->assertStringContainsString('<target', $outputContent);
        } finally {
            @unlink($outputFile);
        }
    }

    /**
     * @covers \Matecat\XliffParser\XliffReplacer\Xliff12
     *
     * Test Xliff12 with negative mrk_id (covers line 256)
     */
    #[Test]
    public function xliff12_replace_translation_with_negative_mrk_id(): void
    {
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'An English string',
                'internal_id' => '251971551065',
                'mrk_id' => '-1', // Negative mrk_id that is not null
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Une chaîne en anglais',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '3.00',
                'raw_word_count' => '3.00',
            ],
        ]);

        $inputFile = $this->getTestFilePath('12/file-with-notes-converted-nobase64.xliff');
        $outputFile = sys_get_temp_dir() . '/negative_mrk_' . uniqid() . '.xliff';

        try {
            $replacer = XliffReplacerFactory::getInstance(
                $inputFile,
                $data['data'],
                $data['transUnits'],
                'fr-fr',
                $outputFile,
                false
            );
            $replacer->replaceTranslation();

            $this->assertFileExists($outputFile);
            $outputContent = (string)file_get_contents($outputFile);
            // Verify the translation was applied
            $this->assertStringContainsString('Une chaîne en anglais', $outputContent);
        } finally {
            @unlink($outputFile);
        }
    }

    /**
     * @covers \Matecat\XliffParser\XliffReplacer\Xliff20
     *
     * Test Xliff20 with translate="no" units (covers lines 170-174 in Xliff20)
     */
    #[Test]
    public function xliff20_replace_translation_with_non_translatable_unit_and_not_all_segments(): void
    {
        // Only provide translation for the first unit, leave out the translate="no" unit
        $data = $this->getData([
            [
                'sid' => '1',
                'segment' => 'Hello World',
                'internal_id' => '0',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'Bonjour le Monde',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '2.00',
                'raw_word_count' => '2.00',
            ],
            [
                'sid' => '2',
                'segment' => 'Do not translate this',
                'internal_id' => '1',
                'mrk_id' => '',
                'prev_tags' => '',
                'succ_tags' => '',
                'mrk_prev_tags' => null,
                'mrk_succ_tags' => null,
                'translation' => 'SHOULD NOT EXISTS',
                'status' => 'TRANSLATED',
                'error' => '',
                'eq_word_count' => '2.00',
                'raw_word_count' => '2.00',
            ]
        ]);

        $inputFile = $this->getTestFilePath('20/xliff20-with-translate-no.xlf');
        $outputFile = sys_get_temp_dir() . '/xliff20_translate_no_' . uniqid() . '.xlf';

        try {
            $replacer = XliffReplacerFactory::getInstance(
                $inputFile,
                $data['data'],
                $data['transUnits'],
                'fr',
                $outputFile,
                false
            );
            $replacer->replaceTranslation();

            $this->assertFileExists($outputFile);
            $outputContent = (string)file_get_contents($outputFile);

            // Verify the translatable unit got translated
            $this->assertStringContainsString('Bonjour le Monde', $outputContent);

            // The non-translatable unit should still be there with original content
            $this->assertStringContainsString('Do not translate this', $outputContent);
            $this->assertStringNotContainsString('SHOULD NOT EXISTS', $outputContent);

            // Verify that the non-translatable unit was not translated.
            // Furthermore, the translation value is not provided
            $this->assertStringContainsString('<target></target>', $outputContent);

        } finally {
            @unlink($outputFile);
        }
    }
}

class DummyXliffReplacerCallbackWhichReturnTrue implements XliffReplacerCallbackInterface
{
    /**
     * @inheritDoc
     */
    public function thereAreErrors(
        int $segmentId,
        string $segment,
        string $translation,
        ?array $dataRefMap = [],
        ?string $error = null
    ): bool {
        return true;
    }
}
