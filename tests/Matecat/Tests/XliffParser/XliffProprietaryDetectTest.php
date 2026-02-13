<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;


use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\XliffUtils\XliffProprietaryDetect;
use PHPUnit\Framework\Attributes\Test;

class XliffProprietaryDetectTest extends Base
{
    #[Test]
    public function return_empty_info_array_from_not_xliff_file(): void
    {
        $info = XliffProprietaryDetect::getInfo($this->getTestFilePath('sample.txt'));

        $this->assertFalse($info['proprietary']);
        $this->assertNull($info['proprietary_name']);
        $this->assertNull($info['proprietary_short_name']);
        $this->assertNull($info['converter_version']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     */
    #[Test]
    public function can_get_info_from_content(): void
    {
        $info = XliffProprietaryDetect::getInfoByStringData($this->getTestFile('12/file-with-notes-converted-nobase64.xliff'));

        $this->assertEmpty($info['info']);
        $this->assertFalse($info['proprietary']);
        $this->assertEquals(1, $info['version']);
        $this->assertEquals('MateCAT Converter', $info['proprietary_name']);
        $this->assertEquals('matecat_converter', $info['proprietary_short_name']);
        $this->assertEquals('1.0', $info['converter_version']);
    }

    #[Test]
    public function can_get_info_from_file(): void
    {
        $info = XliffProprietaryDetect::getInfo($this->getTestFilePath('12/file-with-notes-converted-nobase64.xliff'));

        $this->assertNotEmpty($info['info']);
        $this->assertFalse($info['proprietary']);
        $this->assertEquals(1, $info['version']);
        $this->assertEquals('MateCAT Converter', $info['proprietary_name']);
        $this->assertEquals('matecat_converter', $info['proprietary_short_name']);
        $this->assertEquals('1.0', $info['converter_version']);
    }

    #[Test]
    public function can_get_info_from_file_v2(): void
    {
        $info = XliffProprietaryDetect::getInfo($this->getTestFilePath('20/sample-20.xlf'));

        $this->assertEquals(2, $info['version']);
        $this->assertEquals('Xliff v2.0 File', $info['proprietary_name']);
        $this->assertEquals('xliff_v2', $info['proprietary_short_name']);
        $this->assertEquals('2.0', $info['converter_version']);
    }

    #[Test]
    public function file_must_be_converted(): void
    {
        $this->assertFalse(XliffProprietaryDetect::fileMustBeConverted($this->getTestFilePath('20/sample-20.xlf'), true, '0.0.0.0'));
        $this->assertFalse(XliffProprietaryDetect::fileMustBeConverted($this->getTestFilePath('12/file-with-notes-converted-nobase64.xliff'), true, '0.0.0.0'));
        $this->assertFalse(XliffProprietaryDetect::fileMustBeConverted($this->getTestFilePath('20/uber/56d591a5-louvre-v2-en_us-fr_fr-PM.xlf'), true, '0.0.0.0'));
    }

    #[Test]
    public function can_get_info_from_xliff_content(): void
    {
        $info = XliffProprietaryDetect::getInfoFromXliffContent($this->getTestFile('20/sample-20.xlf'));

        $this->assertEquals(2, $info['version']);
        $this->assertEquals('Xliff v2.0 File', $info['proprietary_name']);
        $this->assertEquals('xliff_v2', $info['proprietary_short_name']);
        $this->assertEquals('2.0', $info['converter_version']);
        $this->assertEmpty($info['info']);
    }

    #[Test]
    public function get_info_from_xliff_content_with_empty_string(): void
    {
        $info = XliffProprietaryDetect::getInfoFromXliffContent('');

        $this->assertFalse($info['proprietary']);
        $this->assertNull($info['proprietary_name']);
        $this->assertNull($info['proprietary_short_name']);
        $this->assertNull($info['converter_version']);
    }

    #[Test]
    public function get_info_from_non_existent_file(): void
    {
        $info = XliffProprietaryDetect::getInfo('/non/existent/path/file.xliff');

        $this->assertFalse($info['proprietary']);
        $this->assertNull($info['proprietary_name']);
        $this->assertNull($info['proprietary_short_name']);
        $this->assertNull($info['converter_version']);
    }

    #[Test]
    public function file_must_be_converted_returns_true_for_non_xliff_file(): void
    {
        // Non-xliff files should return true (must be converted)
        $this->assertTrue(XliffProprietaryDetect::fileMustBeConverted($this->getTestFilePath('sample.txt'), false, '0.0.0.0'));
    }

    #[Test]
    public function file_must_be_converted_returns_false_when_not_proprietary_and_no_enforce(): void
    {
        // When enforceOnXliff is false and file is not proprietary, should return false
        $this->assertFalse(XliffProprietaryDetect::fileMustBeConverted(
            $this->getTestFilePath('12/file-with-notes-converted-nobase64.xliff'),
            false, // enforceOnXliff = false
            '0.0.0.0' // filterAddress is set
        ));
    }

    #[Test]
    public function file_must_be_converted_returns_false_for_sdl_xliff_with_enforce(): void
    {
        // SDL xliff files should return false when enforce is active (trados proprietary_short_name)
        $this->assertFalse(XliffProprietaryDetect::fileMustBeConverted(
            $this->getTestFilePath('sdlxliff/piazza.sdlxliff'),
            true, // enforceOnXliff = true
            '0.0.0.0' // filterAddress is set
        ));
    }

    #[Test]
    public function file_must_be_converted_returns_false_for_xliff_without_filter_address_and_not_proprietary(): void
    {
        // When no filter address and file is not proprietary
        $this->assertFalse(XliffProprietaryDetect::fileMustBeConverted(
            $this->getTestFilePath('12/file-with-notes-converted-nobase64.xliff'),
            false,
            null // no filterAddress
        ));
    }

    #[Test]
    public function file_must_be_converted_returns_minus_one_for_proprietary_without_filter_address(): void
    {
        // Need a proprietary file (not SDL/MateCat/xliff_v2) - GlobalSight for example
        // Since we don't have such a file, we need to create one or use a workaround
        // Let's check if there's a GlobalSight file
        // GlobalSight files have xmlns:gs="http://www.globalsight.com/..."

        // Create a temporary file with GlobalSight proprietary content
        $tempFile = sys_get_temp_dir() . '/test_globalsight.xliff';
        $content = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:gs="http://www.globalsight.com/knowledgebase/xliff">
<file source-language="en" target-language="de" datatype="plaintext" original="test.txt">
<body>
<trans-unit id="1">
<source>Hello</source>
<target>Hallo</target>
</trans-unit>
</body>
</file>
</xliff>';
        file_put_contents($tempFile, $content);

        try {
            $result = XliffProprietaryDetect::fileMustBeConverted($tempFile, false, null);
            $this->assertEquals(-1, $result);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function file_must_be_converted_returns_true_for_proprietary_with_filter_and_no_enforce(): void
    {
        // GlobalSight proprietary file with filter address and no enforce should return true
        $tempFile = sys_get_temp_dir() . '/test_globalsight2.xliff';
        $content = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:gs="http://www.globalsight.com/knowledgebase/xliff">
<file source-language="en" target-language="de" datatype="plaintext" original="test.txt">
<body>
<trans-unit id="1">
<source>Hello</source>
<target>Hallo</target>
</trans-unit>
</body>
</file>
</xliff>';
        file_put_contents($tempFile, $content);

        try {
            // proprietary file, enforceOnXliff = false, with filter address
            // This should go into the else branch of the if(!$enforceOnXliff) and return true
            $result = XliffProprietaryDetect::fileMustBeConverted($tempFile, false, '0.0.0.0');
            $this->assertTrue($result);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function file_must_be_converted_returns_true_for_proprietary_with_enforce_and_filter(): void
    {
        // GlobalSight proprietary file with filter address and enforce should return true
        // (not SDL/matecat/xliff_v2)
        $tempFile = sys_get_temp_dir() . '/test_globalsight3.xliff';
        $content = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:gs="http://www.globalsight.com/knowledgebase/xliff">
<file source-language="en" target-language="de" datatype="plaintext" original="test.txt">
<body>
<trans-unit id="1">
<source>Hello</source>
<target>Hallo</target>
</trans-unit>
</body>
</file>
</xliff>';
        file_put_contents($tempFile, $content);

        try {
            // proprietary file, enforceOnXliff = true, with filter address
            // This should return true because GlobalSight is not in the allowed list
            $result = XliffProprietaryDetect::fileMustBeConverted($tempFile, true, '0.0.0.0');
            $this->assertTrue($result);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function can_get_info_from_sdl_xliff_file(): void
    {
        $info = XliffProprietaryDetect::getInfo($this->getTestFilePath('sdlxliff/piazza.sdlxliff'));

        $this->assertFalse($info['proprietary']);
        $this->assertEquals('SDL Studio ', $info['proprietary_name']);
        $this->assertEquals('trados', $info['proprietary_short_name']);
        $this->assertEquals('legacy', $info['converter_version']);
    }

    #[Test]
    public function get_info_from_unreadable_file(): void
    {
        // Create a temporary file that cannot be read
        $tempFile = sys_get_temp_dir() . '/test_unreadable.xliff';
        file_put_contents($tempFile, '<?xml version="1.0"?><xliff version="1.2"/>');
        chmod($tempFile, 0000); // Remove all permissions

        try {
            $info = XliffProprietaryDetect::getInfo($tempFile);

            // Should return empty/default values since file can't be read
            $this->assertFalse($info['proprietary']);
            $this->assertNull($info['proprietary_name']);
        } finally {
            chmod($tempFile, 0644); // Restore permissions for cleanup
            unlink($tempFile);
        }
    }
}
