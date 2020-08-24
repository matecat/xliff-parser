<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;
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
     * @param string $file
     * @param array $expected
     */
    protected function assertXliffEquals($file, array $expected = [])
    {
        $parser = new XliffParser();

        $this->assertEquals($expected, $parser->toArray($this->getTestFile($file)));
    }
}