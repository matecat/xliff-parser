<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Utils\VersionDetector;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;

class VersionDetectorTest extends BaseTest
{
    /**
     * @test
     */
    public function can_throw_exception()
    {
        $detector = new VersionDetector();

        try {
            $detector->detect($this->getTestFile('note.xml'));
        } catch (NotValidFileException $exception){
            $this->assertEquals($exception->getMessage(), 'This is not a valid xliff file');
        }
    }

    /**
     * @test
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    public function can_detect_v1()
    {
        $detector = new VersionDetector();
        $version = $detector->detect($this->getTestFile('file-with-notes-converted-nobase64.xliff'));

        $this->assertEquals($version, '1');
    }

    /**
     * @test
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    public function can_detect_v2()
    {
        $detector = new VersionDetector();
        $version = $detector->detect($this->getTestFile('uber-v2.xliff'));

        $this->assertEquals($version, '2');
    }
}