<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\XliffParser;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use PHPUnit\Framework\Attributes\Test;

class XliffParserPerformanceTest extends Base
{
    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_a_very_large_file(): void
    {

        $this->markTestSkippedInCoverage();

        // read a file with notes inside
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('sdlxliff/55K_segments_english.sdlxliff'));

        $this->assertCount(109167, $parsed['files'][1]['trans-units']);
        $this->assertEquals( '<x id="0"/>', $parsed['files'][1]['trans-units'][1]['source']['raw-content'] );
    }
}
