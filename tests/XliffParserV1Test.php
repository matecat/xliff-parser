<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffParserV1Test extends BaseTest
{
    /**
     * @test
     */
    function parsesWithNoErrors()
    {
        // read a file with notes inside
        $parsed = XliffParser::toArray($this->getTestFile('file-with-notes-converted-nobase64.xliff'));

        var_dump($parsed);
    }
}
