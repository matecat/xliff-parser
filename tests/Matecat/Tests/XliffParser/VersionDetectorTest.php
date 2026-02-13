<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\XliffUtils\XliffVersionDetector;
use PHPUnit\Framework\Attributes\Test;

class VersionDetectorTest extends Base
{
    /**
     * @throws NotSupportedVersionException
     */
    #[Test]
    public function can_throw_exception(): void
    {
        try {
            XliffVersionDetector::detect($this->getTestFile('note.xml'));
        } catch (NotValidFileException $exception) {
            $this->assertEquals('This is not a valid xliff file', $exception->getMessage());
        }
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     */
    #[Test]
    public function can_detect_v1(): void
    {
        $version = XliffVersionDetector::detect($this->getTestFile('12/file-with-notes-converted-nobase64.xliff'));

        $this->assertEquals('1', $version);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     */
    #[Test]
    public function can_detect_v2(): void
    {
        $detector = new XliffVersionDetector();
        $version = $detector->detect($this->getTestFile('20/uber-v2.xliff'));

        $this->assertEquals('2', $version);
    }

    /**
     * @throws NotValidFileException
     */
    #[Test]
    public function throws_exception_for_unsupported_version(): void
    {
        $xliffContent = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="3.0" xmlns="urn:oasis:names:tc:xliff:document:3.0">
    <file id="f1" original="test.txt" source-language="en" target-language="fr">
    </file>
</xliff>';

        $this->expectException(NotSupportedVersionException::class);
        $this->expectExceptionMessage('Not supported version');

        XliffVersionDetector::detect($xliffContent);
    }
}
