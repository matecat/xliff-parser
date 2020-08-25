<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffUtils\XmlParser;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * @param string $file
     *
     * @return false|string
     */
    protected function getTestFile($file)
    {
        return file_get_contents(__DIR__ .'/files/'.$file);
    }

    /**
     * @param $file
     *
     * @return \DOMDocument
     * @throws \Matecat\XliffParser\Exception\InvalidXmlException
     * @throws \Matecat\XliffParser\Exception\XmlParsingException
     */
    protected function getTestFileAsDOMElement($file)
    {
        return XmlParser::parse(file_get_contents(__DIR__ .'/files/'.$file));
    }

    /**
     * @param string $file
     * @param array $expected
     */
    protected function assertXliffEquals($file, array $expected = [])
    {
        $parser = new XliffParser();

        $this->assertEquals($expected, $parser->toArray($this->getTestFile($file)));
    }
}