<?php

namespace Matecat\XliffParser\Tests;

use Exception;

class XliffReplacerValidationTest extends BaseTest {
    /**
     * @test
     */
    public function validate_xliff_20_without_notes_or_original_data() {

        $outputFile = realpath( __DIR__ . '/../tests/files/output/xliff20-without-notes-or-original-data.xliff' );

        try {
            $validate = $this->validateXliff20( $outputFile );
            $this->assertEmpty( $validate );
        } catch ( Exception $exception ) {
            $this->markTestSkipped( 'The xliff validation service is out of order. ' . $exception->getMessage() );
        }
    }

    /**
     * @test
     */
    public function validate_xliff_20_with_mda_prefilled() {
        $outputFile = realpath( __DIR__ . '/../tests/files/output/xliff-20-with-mda.xlf' );

        try {
            $validate = $this->validateXliff20( $outputFile );
            $this->assertEmpty( $validate );
        } catch ( Exception $exception ) {
            $this->markTestSkipped( 'The xliff validation service is out of order. ' . $exception->getMessage() );
        }
    }

    /**
     * @test
     */
    public function invalid_target_language() {
        $outputFile = realpath( __DIR__ . '/../tests/files/output/1111_prova.md.xlf' );

        try {
            $validate = $this->validateXliff20( $outputFile );
            $this->assertNotEmpty( $validate );
        } catch ( Exception $exception ) {
            $this->markTestSkipped( 'The xliff validation service is out of order. ' . $exception->getMessage() );
        }
    }

    /**
     * @test
     */
    public function validate_sample_xliff_20() {
        $outputFile = realpath( __DIR__ . '/../tests/files/output/sample-20.xlf' );

        try {
            $validate = $this->validateXliff20( $outputFile );
            $this->assertEmpty( $validate );
        } catch ( Exception $exception ) {
            $this->markTestSkipped( 'The xliff validation service is out of order. ' . $exception->getMessage() );
        }
    }
}