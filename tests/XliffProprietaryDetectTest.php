<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffUtils\XliffProprietaryDetect;

class XliffProprietaryDetectTest extends BaseTest
{
    /**
     * @test
     */
    public function can_get_info_from_content()
    {
        $info = XliffProprietaryDetect::getInfoByStringData(file_get_contents(__DIR__ .'/files/file-with-notes-converted-nobase64.xliff'));

        $this->assertEmpty($info['info']);
        $this->assertFalse($info['proprietary']);
        $this->assertEquals($info['version'], 1);
        $this->assertEquals($info['proprietary_name'], 'MateCAT Converter');
        $this->assertEquals($info['proprietary_short_name'], 'matecat_converter');
        $this->assertEquals($info['converter_version'], '1.0');
    }

    /**
     * @test
     */
    public function can_get_info_from_file()
    {
        $info = XliffProprietaryDetect::getInfo(__DIR__ .'/files/file-with-notes-converted-nobase64.xliff');

        $this->assertNotEmpty($info['info']);
        $this->assertFalse($info['proprietary']);
        $this->assertEquals($info['version'], 1);
        $this->assertEquals($info['proprietary_name'], 'MateCAT Converter');
        $this->assertEquals($info['proprietary_short_name'], 'matecat_converter');
        $this->assertEquals($info['converter_version'], '1.0');
    }

    /**
     * @test
     */
    public function can_get_info_from_file_v2()
    {
        $info = XliffProprietaryDetect::getInfo(__DIR__ .'/files/sample-20.xlf');

        $this->assertEquals($info['version'], 2);
        $this->assertNull($info['proprietary_name']);
        $this->assertNull($info['proprietary_short_name']);
    }
}
