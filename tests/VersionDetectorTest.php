<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\XliffUtils\XliffVersionDetector;

class VersionDetectorTest extends BaseTest
{
    /**
     * @test
     * @throws NotValidFileException
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     */
    public function can_throw_exception()
    {
        try {
            XliffVersionDetector::detect($this->getTestFile('note.xml'));
        } catch (NotValidFileException $exception) {
            $this->assertEquals($exception->getMessage(), 'This is not a valid xliff file');
        }
    }

    /**
     * @test
     * @throws NotValidFileException
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     */
    public function can_detect_v1()
    {
        $version = XliffVersionDetector::detect($this->getTestFile('file-with-notes-converted-nobase64.xliff'));

        $this->assertEquals($version, '1');
    }

    /**
     * @test
     * @throws NotValidFileException
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     */
    public function can_detect_v2()
    {
        $detector = new XliffVersionDetector();
        $version = $detector->detect($this->getTestFile('uber-v2.xliff'));

        $this->assertEquals($version, '2');
    }
}
