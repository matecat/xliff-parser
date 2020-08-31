<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Utils\Files;

class FilesTest extends BaseTest
{
    /**
     * @test
     */
    public function can_detect_pathInfo()
    {
        $pathInfo = Files::pathInfo(  __DIR__ .'/files/file-with-notes-converted-nobase64.xliff' );

        $this->assertEquals('file-with-notes-converted-nobase64.xliff', $pathInfo['basename']);
        $this->assertEquals('xliff', $pathInfo['extension']);
        $this->assertEquals('file-with-notes-converted-nobase64', $pathInfo['filename']);
    }

    /**
     * @test
     */
    public function can_detect_xliff()
    {
        $this->assertTrue(Files::isXliff(__DIR__ .'/files/file-with-notes-converted-nobase64.xliff'));
        $this->assertFalse(Files::isXliff(__DIR__ .'/files/note.xml'));
    }
}