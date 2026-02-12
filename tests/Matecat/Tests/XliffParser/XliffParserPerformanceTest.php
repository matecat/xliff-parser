<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;
use PHPUnit\Framework\Attributes\Test;

class XliffParserPerformanceTest extends Base
{
    #[Test]
    public function can_parse_a_very_large_file()
    {

        $isCoverage = (bool)count(array_filter($_SERVER['argv'], fn($arg) => str_contains($arg, 'coverage')));

        if ($isCoverage) {
            $this->markTestSkipped(
                'This test is very expensive when coverage is enabled.',
            );
        }

        // read a file with notes inside
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('55K_segments_english.sdlxliff'));

        $this->assertCount(109167, $parsed['files'][1]['trans-units']);
        $this->assertEquals( '<x id="0"/>', $parsed['files'][1]['trans-units'][1]['source']['raw-content'] );
    }
}
