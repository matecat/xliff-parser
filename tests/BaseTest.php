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

        $this->assertEquals($expected, $parser->xliffToArray($this->getTestFile($file)));
    }

    /**
     * Asserts that two associative arrays are similar.
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * @param array $expected
     * @param array $array
     */
    protected function assertArraySimilar(array $expected, array $array)
    {
        $this->assertTrue(count(array_diff_key($array, $expected)) === 0);

        foreach ($expected as $key => $value) {
            if (is_array($value)) {
                $this->assertArraySimilar($value, $array[$key]);
            } else {
                $this->assertStringContainsString(trim($value), trim($array[$key]));
            }
        }
    }
}
