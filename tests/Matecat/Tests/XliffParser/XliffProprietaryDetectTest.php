<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;


use Matecat\XliffParser\XliffUtils\XliffProprietaryDetect;
use PHPUnit\Framework\Attributes\Test;

class XliffProprietaryDetectTest extends Base
{
    #[Test]
    public function return_empty_info_array_from_not_xliff_file()
    {
        $info = XliffProprietaryDetect::getInfo($this->getTestFilePath('sample.txt'));

        $this->assertFalse($info['proprietary']);
        $this->assertNull($info['proprietary_name']);
        $this->assertNull($info['proprietary_short_name']);
        $this->assertNull($info['converter_version']);
    }

    #[Test]
    public function can_get_info_from_content()
    {
        $info = XliffProprietaryDetect::getInfoByStringData($this->getTestFile('file-with-notes-converted-nobase64.xliff'));

        $this->assertEmpty($info['info']);
        $this->assertFalse($info['proprietary']);
        $this->assertEquals($info['version'], 1);
        $this->assertEquals($info['proprietary_name'], 'MateCAT Converter');
        $this->assertEquals($info['proprietary_short_name'], 'matecat_converter');
        $this->assertEquals($info['converter_version'], '1.0');
    }

    #[Test]
    public function can_get_info_from_file()
    {
        $info = XliffProprietaryDetect::getInfo($this->getTestFilePath('file-with-notes-converted-nobase64.xliff'));

        $this->assertNotEmpty($info['info']);
        $this->assertFalse($info['proprietary']);
        $this->assertEquals($info['version'], 1);
        $this->assertEquals($info['proprietary_name'], 'MateCAT Converter');
        $this->assertEquals($info['proprietary_short_name'], 'matecat_converter');
        $this->assertEquals($info['converter_version'], '1.0');
    }

    #[Test]
    public function can_get_info_from_file_v2()
    {
        $info = XliffProprietaryDetect::getInfo($this->getTestFilePath('sample-20.xlf'));

        $this->assertEquals($info['version'], 2);
        $this->assertEquals($info['proprietary_name'], 'Xliff v2.0 File');
        $this->assertEquals($info['proprietary_short_name'], 'xliff_v2');
        $this->assertEquals($info['converter_version'], '2.0');
    }

    #[Test]
    public function file_must_be_converted()
    {
        $this->assertFalse(XliffProprietaryDetect::fileMustBeConverted($this->getTestFilePath('sample-20.xlf'), true, '0.0.0.0'));
        $this->assertFalse(XliffProprietaryDetect::fileMustBeConverted($this->getTestFilePath('file-with-notes-converted-nobase64.xliff'), true, '0.0.0.0'));
        $this->assertFalse(XliffProprietaryDetect::fileMustBeConverted($this->getTestFilePath('uber/56d591a5-louvre-v2-en_us-fr_fr-PM.xlf'), true, '0.0.0.0'));
    }
}
