<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffReplacerValidationTest extends BaseTest
{
    /**
     * @test
     */
    public function validate_xliff_20_without_notes_or_original_data()
    {
        $outputFile = __DIR__.'/../tests/files/output/xliff20-without-notes-or-original-data.xliff';
        $output = file_get_contents($outputFile);

        $validate = $this->validateXliff20($output);

        $this->assertEmpty($validate);
    }

    /**
     * @test
     */
    public function validate_xliff_20_with_mda_prefilled()
    {
        $outputFile = __DIR__.'/../tests/files/output/xliff-20-with-mda.xlf';
        $output = file_get_contents($outputFile);

        $validate = $this->validateXliff20($output);

        $this->assertEmpty($validate);
    }

    /**
     * @test
     */
    public function validate_sample_xliff_20_without_target()
    {
        $outputFile = __DIR__.'/../tests/files/output/1111_prova.md.xlf';
        $output = file_get_contents($outputFile);

        $validate = $this->validateXliff20($output);

        $this->assertEmpty($validate);
    }

    /**
     * @test
     */
    public function validate_sample_xliff_20()
    {
        $outputFile = __DIR__.'/../tests/files/output/sample-20.xlf';
        $output = file_get_contents($outputFile);

        $validate = $this->validateXliff20($output);

        $this->assertEmpty($validate);
    }
}