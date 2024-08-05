<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffReplacer\XliffReplacerCallbackInterface;

class XliffReplacerTest extends BaseTest {

    /**
     * @test
     */
    public function can_replace_a_xliff_12_with_context_group() {

        $data = $this->getData( [
                [
                        "data_ref_map"   => null,
                        "eq_word_count"  => "0.00",
                        "error"          => "",
                        "internal_id"    => "tu1",
                        "mrk_id"         => "0",
                        "mrk_prev_tags"  => null,
                        "mrk_succ_tags"  => null,
                        "prev_tags"      => "",
                        "r2"             => null,
                        "raw_word_count" => "1.00",
                        "segment"        => "Confirm",
                        "sid"            => "119092",
                        "source_page"    => null,
                        "status"         => "TRANSLATED",
                        "succ_tags"      => "",
                        "translation"    => "يتأكد",
                ],
        ] );

        $inputFile  = __DIR__ . '/../tests/files/13578661#IFgFi8oGn6.xlf';
        $outputFile = __DIR__ . '/../tests/files/output/13578661#IFgFi8oGn6.xlf';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'ar-JO', $outputFile );

        $output = $xliffParser->xliffToArray( file_get_contents( $outputFile ) );
        $this->assertEquals( $output[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'seg-source' ][ 0 ][ 'raw-content' ], 'Confirm' );
        $this->assertEquals( $output[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'seg-target' ][ 0 ][ 'raw-content' ], "يتأكد" );

        $outputRawContent = file_get_contents( $outputFile );
        $this->assertTrue( mb_strpos( $outputRawContent, "يتأكد" ) > 0 );
        $this->assertTrue( mb_strpos( $outputRawContent, '</xliff>' ) > 0 );
        $this->assertTrue( mb_strpos( $outputRawContent, '</file>' ) > 0 );
        $this->assertTrue( mb_strpos( $outputRawContent, '</body>' ) > 0 );

    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_without_trgLang_attribute() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => 'Deutsch',
                        'internal_id'    => 'I11:359;122:3567',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Alemán',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
        ] );

        $inputFile  = __DIR__ . '/../tests/files/no-trgLang.xliff';
        $outputFile = __DIR__ . '/../tests/files/output/no-trgLang.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'es-ES', $outputFile );

        $output = $xliffParser->xliffToArray( file_get_contents( $outputFile ) );

        $this->assertEquals( 'es-ES', $output[ 'files' ][ 1 ][ 'attr' ][ 'target-language' ] );
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_with_the_correct_counts() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => 'bla bla bla',
                        'internal_id'    => '0',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 20,
                        'raw_word_count' => 30,
                ],
                [
                        'sid'            => 2,
                        'segment'        => 'bla bla bla',
                        'internal_id'    => '1',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 40,
                        'raw_word_count' => 60,
                ],
        ] );

        $inputFile  = __DIR__ . '/../tests/files/uber/uber-counts.xliff';
        $outputFile = __DIR__ . '/../tests/files/uber/output/uber-counts.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'sk-SK', $outputFile );

        $output = file_get_contents( $outputFile );

        preg_match_all( '/<mda:meta type="x-matecat-raw">(.*)<\/mda:meta>/', $output, $raw );
        preg_match_all( '/<mda:meta type="x-matecat-weighted">(.*)<\/mda:meta>/', $output, $weighted );

        $this->assertEquals( 30, $raw[ 1 ][ 0 ] );
        $this->assertEquals( 60, $raw[ 1 ][ 1 ] );
        $this->assertEquals( 20, $weighted[ 1 ][ 0 ] );
        $this->assertEquals( 40, $weighted[ 1 ][ 1 ] );

        // check for metaGroup attributes
        preg_match_all( '/<mda:metaGroup id="(.*)" category="(.*)">/', $output, $metaGroup );

        $this->assertEquals( 'word_count_tu[0][0]', $metaGroup[ 1 ][ 0 ] );
        $this->assertEquals( 'word_count_tu[1][0]', $metaGroup[ 1 ][ 1 ] );
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_with_mda_without_notes_or_original_data() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => 'Join our webinar: “<pc dataRefEnd="d2" dataRefStart="d1" id="1" subType="xlf:b" type="fmt">Machine Learning in Cyber Security: What It Is and What It Isn\'t</pc>”',
                        'internal_id'    => 'u1',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
        ] );

        $inputFile  = __DIR__ . '/../tests/files/xliff20-without-notes-or-original-data.xliff';
        $outputFile = __DIR__ . '/../tests/files/output/xliff20-without-notes-or-original-data.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'sk-SK', $outputFile );

        $output = file_get_contents( $outputFile );

        // check if there is only one <mda:metadata>
        $this->assertEquals( 1, substr_count( $output, '<mda:metadata>' ) );
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_with_mda_without_duplicate_it() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => 'Join our webinar: “<pc dataRefEnd="d2" dataRefStart="d1" id="1" subType="xlf:b" type="fmt">Machine Learning in Cyber Security: What It Is and What It Isn\'t</pc>”',
                        'internal_id'    => 'u1-1',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
        ] );

        $inputFile  = __DIR__ . '/../tests/files/xliff-20-with-mda.xlf';
        $outputFile = __DIR__ . '/../tests/files/output/xliff-20-with-mda.xlf';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'sk-SK', $outputFile );

        $output = file_get_contents( $outputFile );

        // check if there is only one <mda:metadata>
        $this->assertEquals( 1, substr_count( $output, '<mda:metadata>' ) );
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_12_without_target() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => 'Bla Bla',
                        'internal_id'    => 'NFDBB2FA9-tu519',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid'            => 2,
                        'segment'        => 'Bla Bla',
                        'internal_id'    => 'NFDBB2FA9-tu52',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid'            => 3,
                        'segment'        => 'Bla Bla',
                        'internal_id'    => 'NFDBB2FA9-tu523',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid'            => 4,
                        'segment'        => 'Bla Bla',
                        'internal_id'    => 'NFDBB2FA9-tu524',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid'            => 5,
                        'segment'        => 'Bla Bla',
                        'internal_id'    => 'NFDBB2FA9-tu525',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
        ] );

        $inputFile  = __DIR__ . '/../tests/files/file-with-nested-group-and-missing-target.xliff';
        $outputFile = __DIR__ . '/../tests/files/output/file-with-nested-group-and-missing-target.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'sk-SK', $outputFile );
        $output = $xliffParser->xliffToArray( file_get_contents( $outputFile ) );

        $expected = 'Bla bla bla';

        $this->assertEquals( $output[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'target' ][ 'raw-content' ], $expected );
        $this->assertEquals( $output[ 'files' ][ 3 ][ 'trans-units' ][ 2 ][ 'target' ][ 'raw-content' ], $expected );
        $this->assertEquals( $output[ 'files' ][ 3 ][ 'trans-units' ][ 3 ][ 'target' ][ 'raw-content' ], $expected );
    }

    /**
     * @test
     */
    public function can_replace_an_intermediate_xliff_12_without_target() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => 'Bla Bla',
                        'internal_id'    => 'NFDBB2FA9-tu1',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
        ] );

        $inputFile  = __DIR__ . '/../tests/files/intermediate_xliff.xliff';
        $outputFile = __DIR__ . '/../tests/files/output/intermediate_xliff.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'sk-SK', $outputFile );
        $output = $xliffParser->xliffToArray( file_get_contents( $outputFile ) );

        $file      = $output[ 'files' ][ 3 ];
        $transUnit = $file[ 'trans-units' ][ 1 ];
        $segSource = $transUnit[ 'seg-source' ];
        $source    = $transUnit[ 'source' ];
        $target    = $transUnit[ 'target' ];

        $this->assertEquals( $file[ 'attr' ][ 'data-type' ], 'x-undefined' );
        $this->assertEquals( $file[ 'attr' ][ 'original' ], 'word/document.xml' );
        $this->assertEquals( $file[ 'attr' ][ 'source-language' ], 'en-GB' );
        $this->assertEquals( $file[ 'attr' ][ 'target-language' ], 'sk-SK' );
        $this->assertEquals( $transUnit[ 'attr' ][ 'id' ], 'NFDBB2FA9-tu1' );
        $this->assertEquals( $segSource[ 0 ][ 'mid' ], 0 );
        $this->assertEquals( $segSource[ 0 ][ 'raw-content' ], 'The system for creative people is broken' );
        $this->assertEquals( $source[ 'raw-content' ], 'The system for creative people is broken' );
        $this->assertEquals( $source[ 'attr' ][ 'xml:lang' ], 'en-GB' );
        $this->assertEquals( $target[ 'raw-content' ], 'Bla bla bla' );
        $this->assertEquals( $target[ 'attr' ][ 'xml:lang' ], 'sk-SK' );
        $this->assertEquals( $target[ 'attr' ][ 'state' ], 'translated' );
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_10_without_target_lang() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => 'Image showing Italian Patreon creators',
                        'internal_id'    => 'pendo-image-e3aaf7b7|alt',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Bla bla bla',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ]
        ] );

        $inputFile  = __DIR__ . '/../tests/files/no-target.xliff';
        $outputFile = __DIR__ . '/../tests/files/output/no-target.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'it-it', $outputFile );
        $output = $xliffParser->xliffToArray( file_get_contents( $outputFile ) );

        $this->assertEquals( $output[ 'files' ][ 1 ][ 'attr' ][ 'target-language' ], 'it-it' );
    }

    /**
     * @test
     */
    public function should_replace_a_translation_with_0_as_string() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => 'Image showing Italian Patreon creators',
                        'internal_id'    => 'pendo-image-e3aaf7b7|alt',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => '0', // <----
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ]
        ] );

        $inputFile  = __DIR__ . '/../tests/files/no-target.xliff';
        $outputFile = __DIR__ . '/../tests/files/output/no-target.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'it-it', $outputFile );
        $output = $xliffParser->xliffToArray( file_get_contents( $outputFile ) );

        $this->assertEquals( $output[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'target' ][ 'raw-content' ], '0' );


    }

    /**
     * @test
     */
    public function can_replace_a_xliff_10() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => '<g id="1">&#128076;&#127995;</g>',
                        'internal_id'    => 'NFDBB2FA9-tu519',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => '<g id="1">&#128076;&#127995;</g>',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ]
        ] );

        $inputFile  = __DIR__ . '/../tests/files/file-with-emoji.xliff';
        $outputFile = __DIR__ . '/../tests/files/output/file-with-emoji.xliff';

        $xliffParser = new XliffParser();
        $xliffParser->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'fr-fr', $outputFile );
        $output   = $xliffParser->xliffToArray( file_get_contents( $outputFile ) );
        $expected = '<g id="1">&#128076;&#127995;</g>';

        $this->assertEquals( $expected, $output[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'target' ][ 'raw-content' ] );
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_without_target() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => 'Titolo del documento',
                        'internal_id'    => 'tu1',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Document title',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 2,
                ],
                [
                        'sid'            => 2,
                        'segment'        => 'Titolo del documento2',
                        'internal_id'    => 'tu1',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Document title2',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 3,
                        'raw_word_count' => 4,
                ],
                [
                        'sid'            => 3,
                        'segment'        => 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.',
                        'internal_id'    => 'tu2',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">cursive</pc>.',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 4,
                        'raw_word_count' => 5,
                ],
        ] );

        $inputFile  = __DIR__ . '/../tests/files/1111_prova.md.xlf';
        $outputFile = __DIR__ . '/../tests/files/output/1111_prova.md.xlf';

        ( new XliffParser() )->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'en-en', $outputFile, false );
        $output    = ( new XliffParser() )->xliffToArray( file_get_contents( $outputFile ) );
        $expected  = 'Document title';
        $expected2 = 'Document title2';
        $expected3 = 'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">cursive</pc>.';

        $unit1 = $output[ 'files' ][ 1 ][ 'trans-units' ][ 1 ];
        $unit2 = $output[ 'files' ][ 1 ][ 'trans-units' ][ 2 ];

        $this->assertEquals( $unit1[ 'attr' ][ 'id' ], 'tu1' );
        $this->assertEquals( $unit2[ 'attr' ][ 'id' ], 'tu2' );
        $this->assertEquals( $unit1[ 'source' ][ 'attr' ][ 0 ][ 'xml:space' ], 'preserve' );
        $this->assertEquals( $unit1[ 'source' ][ 'attr' ][ 1 ][ 'xml:space' ], 'preserve' );
        $this->assertEquals( $unit2[ 'source' ][ 'attr' ][ 0 ][ 'xml:space' ], 'preserve' );
        $this->assertEquals( $unit1[ 'source' ][ 'raw-content' ][ 0 ], 'Titolo del documento' );
        $this->assertEquals( $unit1[ 'source' ][ 'raw-content' ][ 1 ], 'Titolo del documento2' );
        $this->assertEquals( $unit2[ 'source' ][ 'raw-content' ][ 0 ], 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.' );
        $this->assertEquals( $unit1[ 'seg-source' ][ 0 ][ 'mid' ], 0 );
        $this->assertEquals( $unit1[ 'seg-source' ][ 0 ][ 'raw-content' ], 'Titolo del documento' );
        $this->assertEquals( $unit1[ 'seg-source' ][ 1 ][ 'mid' ], 1 );
        $this->assertEquals( $unit1[ 'seg-source' ][ 1 ][ 'raw-content' ], 'Titolo del documento2' );
        $this->assertEquals( $unit2[ 'seg-source' ][ 0 ][ 'mid' ], 0 );
        $this->assertEquals( $unit2[ 'seg-source' ][ 0 ][ 'raw-content' ], 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.' );
        $this->assertNotEmpty( $unit1[ 'target' ][ 'raw-content' ] );
        $this->assertNotEmpty( $unit1[ 'target' ][ 'raw-content' ] );
        $this->assertNotEmpty( $unit2[ 'target' ][ 'raw-content' ] );
        $this->assertEquals( $expected, $unit1[ 'target' ][ 'raw-content' ][ 0 ] );
        $this->assertEquals( $expected2, $unit1[ 'target' ][ 'raw-content' ][ 1 ] );
        $this->assertEquals( $expected3, $unit2[ 'target' ][ 'raw-content' ][ 0 ] );
        $this->assertNotEmpty( $unit1[ 'seg-target' ][ 0 ] );
        $this->assertNotEmpty( $unit1[ 'seg-target' ][ 1 ] );
        $this->assertNotEmpty( $unit2[ 'seg-target' ][ 0 ] );
        $this->assertEquals( $unit1[ 'seg-target' ][ 0 ][ 'mid' ], 0 );
        $this->assertEquals( $unit1[ 'seg-target' ][ 0 ][ 'raw-content' ], 'Document title' );
        $this->assertEquals( $unit1[ 'seg-target' ][ 1 ][ 'mid' ], 1 );
        $this->assertEquals( $unit1[ 'seg-target' ][ 1 ][ 'raw-content' ], 'Document title2' );
        $this->assertEquals( $unit2[ 'seg-target' ][ 0 ][ 'mid' ], 0 );
        $this->assertEquals( $unit2[ 'seg-target' ][ 0 ][ 'raw-content' ], 'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">cursive</pc>.' );

        // check counters
        preg_match_all( '/<mda:meta type="x-matecat-raw">(.*?)<\/mda:meta>/s', file_get_contents( $outputFile ), $rawWords );
        preg_match_all( '/<mda:meta type="x-matecat-weighted">(.*?)<\/mda:meta>/s', file_get_contents( $outputFile ), $weightedWords );

        $this->assertEquals( $rawWords[ 1 ][ 1 ], 5 );
        $this->assertEquals( $weightedWords[ 1 ][ 1 ], 4 );
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_with_no_errors() {
        $data       = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => '<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>',
                        'internal_id'    => 'u1',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => '<pc id="1">Buongiorno al <mrk id="m2" type="term">Mondo</mrk> !</pc>',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'r2'             => null,
                        'eq_word_count'  => 100,
                        'raw_word_count' => 200,
                ],
                [
                        'sid'            => 2,
                        'segment'        => '<pc id="1">Hello <mrk id="m2" type="term">World2</mrk> !</pc>',
                        'internal_id'    => 'u2',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => '<pc id="2">Buongiorno al <mrk id="m2" type="term">Mondo2</mrk> !</pc>',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'r2'             => null,
                        'eq_word_count'  => 200,
                        'raw_word_count' => 300,
                ],
        ] );
        $inputFile  = __DIR__ . '/../tests/files/sample-20.xlf';
        $outputFile = __DIR__ . '/../tests/files/output/sample-20.xlf';

        ( new XliffParser() )->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'fr-fr', $outputFile, false, new DummyXliffReplacerCallback() );
        $output   = ( new XliffParser() )->xliffToArray( file_get_contents( $outputFile ) );
        $expected = '<pc id="1">Buongiorno al <mrk id="m2" type="term">Mondo</mrk> !</pc>';

        $this->assertEquals( $expected, $output[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'target' ][ 'raw-content' ][ 0 ] );
    }

    /**
     * @test
     */
    public function can_replace_a_xliff_20_with_consistency_errors() {
        $data       = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => '<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>',
                        'internal_id'    => 'u1',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => '<pc id="1">Buongiorno al <mrk id="m2" type="term">Mondo</mrk> !</pc>',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'r2'             => null,
                        'eq_word_count'  => 100,
                        'raw_word_count' => 200,
                ],
                [
                        'sid'            => 2,
                        'segment'        => '<pc id="1">Hello <mrk id="m2" type="term">World2</mrk> !</pc>',
                        'internal_id'    => 'u2',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => '<pc id="2">Buongiorno al <mrk id="m2" type="term">Mondo2</mrk> !</pc>',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'r2'             => null,
                        'eq_word_count'  => 200,
                        'raw_word_count' => 300,
                ],
        ] );
        $inputFile  = __DIR__ . '/../tests/files/sample-20.xlf';
        $outputFile = __DIR__ . '/../tests/files/output/sample-20.xlf';

        ( new XliffParser() )->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'fr-fr', $outputFile, false, new DummyXliffReplacerCallbackWhichReturnTrue() );
        $output   = ( new XliffParser() )->xliffToArray( file_get_contents( $outputFile ) );
        $expected = '|||UNTRANSLATED_CONTENT_START|||<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>|||UNTRANSLATED_CONTENT_END|||';

        $this->assertEquals( $expected, $output[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'target' ][ 'raw-content' ][ 0 ] );
    }

    /**
     * In this case the replacer must do not replace original target
     *
     * @test
     */
    public function can_replace_a_xliff_12_with__translate_no() {
        $data = $this->getData( [ ] );

        $inputFile  = __DIR__ . '/../tests/files/Working_with_the_Review_tool_single_tu.xlf';
        $outputFile = __DIR__ . '/../tests/files/output/Working_with_the_Review_tool_single_tu.xlf';

        ( new XliffParser() )->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'it-it', $outputFile, false );
        $output   = ( new XliffParser() )->xliffToArray( file_get_contents( $outputFile ) );
        $expected = '<mrk mtype="seg" mid="1" MadCap:segmentStatus="Untranslated" MadCap:matchPercent="0"/>';

        $this->assertEquals( $expected, $output[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'target' ][ 'raw-content' ] );
    }

    /**
     * In this case the replacer must do not replace original target
     *
     * @test
     */
    public function can_replace_a_xliff_12_with_mrk_and_g() {
        $data = $this->getData( [
                [
                        'sid'            => 1,
                        'segment'        => '<g id="1"><mrk mid="0" mtype="seg">An English string with g tags</mrk></g>',
                        'internal_id'    => '251971551065',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => '<g id="1"><mrk mid="0" mtype="seg">Paperone</mrk></g>',
                        'status'         => TranslationStatus::STATUS_APPROVED,
                        'r2'             => 1,
                        'eq_word_count'  => 3,
                        'raw_word_count' => 6,
                ],
                [
                        'sid'            => 2,
                        'segment'        => '<mrk mid="0" mtype="seg">This unit has a comment too</mrk>',
                        'internal_id'    => '251971551066',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => '<mrk mid="0" mtype="seg">Paperino</mrk>',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'r2'             => 1,
                        'eq_word_count'  => 3,
                        'raw_word_count' => 6,
                ],
                [
                        'sid'            => 3,
                        'segment'        => '<mrk mid="0" mtype="seg">Source</mrk>',
                        'internal_id'    => '251971551068',
                        'mrk_id'         => '0',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'Sorgente',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid'            => 4,
                        'segment'        => '<mrk mid="1" mtype="seg">of</mrk>',
                        'internal_id'    => '251971551068',
                        'mrk_id'         => '1',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'di',
                        'status'         => TranslationStatus::STATUS_APPROVED2,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid'            => 5,
                        'segment'        => '<mrk mid="2" mtype="seg">truth</mrk>',
                        'internal_id'    => '251971551068',
                        'mrk_id'         => '2',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => 'verità',
                        'status'         => TranslationStatus::STATUS_APPROVED,
                        'eq_word_count'  => 1,
                        'raw_word_count' => 1,
                ],
                [
                        'sid'            => 6,
                        'segment'        => '<mrk mid="0" mtype="seg">An English string</mrk>',
                        'internal_id'    => '251971551067',
                        'mrk_id'         => '',
                        'prev_tags'      => '',
                        'succ_tags'      => '',
                        'mrk_prev_tags'  => '',
                        'mrk_succ_tags'  => '',
                        'translation'    => '<g id="1"><g id="2"><mrk mid="0" mtype="seg"><ex id="1">Paperoga</ex></mrk></g></g>',
                        'status'         => TranslationStatus::STATUS_TRANSLATED,
                        'r2'             => 1,
                        'eq_word_count'  => 2,
                        'raw_word_count' => 3,
                ],
        ] );

        $inputFile  = __DIR__ . '/../tests/files/file-with-notes-and-no-target-seg-source-with-external-g-tag.xliff';
        $outputFile = __DIR__ . '/../tests/files/output/file-with-notes-and-no-target-seg-source-with-external-g-tag.xliff';

        ( new XliffParser() )->replaceTranslation( $inputFile, $data[ 'data' ], $data[ 'transUnits' ], 'it-it', $outputFile, false );
        $output   = ( new XliffParser() )->xliffToArray( file_get_contents( $outputFile ) );

        $expected = '<g id="1"><mrk mid="0" mtype="seg">Paperone</mrk></g>';
        $this->assertEquals( $expected, $output[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'target' ][ 'raw-content' ] );

        $expected = '<mrk mid="0" mtype="seg">Paperino</mrk>';
        $this->assertEquals( $expected, $output[ 'files' ][ 3 ][ 'trans-units' ][ 2 ][ 'target' ][ 'raw-content' ] );

        $expected = '<mrk mid="0" mtype="seg">Sorgente</mrk><mrk mid="1" mtype="seg">di</mrk><mrk mid="2" mtype="seg">verità</mrk>';
        $this->assertEquals( $expected, $output[ 'files' ][ 3 ][ 'trans-units' ][ 3 ][ 'target' ][ 'raw-content' ] );

        $expected = '<g id="1"><g id="2"><mrk mid="0" mtype="seg"><ex id="1">Paperoga</ex></mrk></g></g>';
        $this->assertEquals( $expected, $output[ 'files' ][ 3 ][ 'trans-units' ][ 4 ][ 'target' ][ 'raw-content' ] );

    }

}

class RealXliffReplacerCallback implements XliffReplacerCallbackInterface {
    /**
     * @inheritDoc
     */
    public function thereAreErrors( $segmentId, $segment, $translation, array $dataRefMap = [], $error = null ) {
        return false;
    }
}

class DummyXliffReplacerCallback implements XliffReplacerCallbackInterface {
    /**
     * @inheritDoc
     */
    public function thereAreErrors( $segmentId, $segment, $translation, array $dataRefMap = [], $error = null ) {
        return false;
    }
}

class DummyXliffReplacerCallbackWhichReturnTrue implements XliffReplacerCallbackInterface {
    /**
     * @inheritDoc
     */
    public function thereAreErrors( $segmentId, $segment, $translation, array $dataRefMap = [], $error = null ) {
        return true;
    }
}
