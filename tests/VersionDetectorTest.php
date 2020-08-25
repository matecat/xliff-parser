<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\XliffUtils\VersionDetector;

class VersionDetectorTest extends BaseTest
{
//    /**
//     * @test
//     * @throws NotValidFileException
//     * @throws \Matecat\XliffParser\Exception\InvalidXmlException
//     * @throws \Matecat\XliffParser\Exception\XmlParsingException
//     */
//    public function can_throw_exception()
//    {
//        try {
//            VersionDetector::detect($this->getTestFileAsDOMElement('note.xml'));
//        } catch (NotValidFileException $exception){
//            $this->assertEquals($exception->getMessage(), 'This is not a valid xliff file');
//        }
//    }

    /**
     * @test
     * @throws NotValidFileException
     * @throws \Matecat\XliffParser\Exception\InvalidXmlException
     * @throws \Matecat\XliffParser\Exception\XmlParsingException
     */
    public function can_detect_v1()
    {
        $version = VersionDetector::detect($this->getTestFileAsDOMElement('file-with-notes-converted-nobase64.xliff'));

        $this->assertEquals($version, '1');
    }

//    /**
//     * @test
//     * @throws NotValidFileException
//     * @throws \Matecat\XliffParser\Exception\InvalidXmlException
//     * @throws \Matecat\XliffParser\Exception\XmlParsingException
//     */
//    public function can_detect_v2()
//    {
//        $detector = new VersionDetector();
//        $version = $detector->detect($this->getTestFileAsDOMElement('uber-v2.xliff'));
//
//        $this->assertEquals($version, '2');
//    }
}