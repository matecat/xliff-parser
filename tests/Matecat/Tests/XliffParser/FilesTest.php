<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Utils\Files;
use PHPUnit\Framework\Attributes\Test;

class FilesTest extends Base
{
    #[Test]
    public function can_detect_pathInfo(): void
    {
        $pathInfo = Files::pathInfo($this->getTestFilePath('12/file-with-notes-converted-nobase64.xliff'));

        $this->assertIsArray($pathInfo);
        $this->assertEquals('file-with-notes-converted-nobase64.xliff', $pathInfo['basename']);
        $this->assertEquals('xliff', $pathInfo['extension']);
        $this->assertEquals('file-with-notes-converted-nobase64', $pathInfo['filename']);
    }

    #[Test]
    public function can_detect_xliff(): void
    {
        $this->assertTrue(Files::isXliff($this->getTestFilePath('12/file-with-notes-converted-nobase64.xliff')));
        $this->assertFalse(Files::isXliff($this->getTestFilePath('note.xml')));
    }

    #[Test]
    public function pathInfo_returns_all_components_by_default(): void
    {
        $path = '/path/to/my/file.test.txt';
        $pathInfo = Files::pathInfo($path);

        $this->assertIsArray($pathInfo);
        $this->assertEquals('/path/to/my', $pathInfo['dirname']);
        $this->assertEquals('file.test.txt', $pathInfo['basename']);
        $this->assertEquals('txt', $pathInfo['extension']);
        $this->assertEquals('file.test', $pathInfo['filename']);
    }

    #[Test]
    public function pathInfo_returns_only_dirname_when_requested(): void
    {
        $path = '/path/to/my/file.txt';
        $result = Files::pathInfo($path, PATHINFO_DIRNAME);

        $this->assertEquals('/path/to/my', $result);
    }

    #[Test]
    public function pathInfo_returns_only_basename_when_requested(): void
    {
        $path = '/path/to/my/file.txt';
        $result = Files::pathInfo($path, PATHINFO_BASENAME);

        $this->assertEquals('file.txt', $result);
    }

    #[Test]
    public function pathInfo_returns_only_extension_when_requested(): void
    {
        $path = '/path/to/my/file.TXT';
        $result = Files::pathInfo($path, PATHINFO_EXTENSION);

        $this->assertEquals('txt', $result);
    }

    #[Test]
    public function pathInfo_returns_only_filename_when_requested(): void
    {
        $path = '/path/to/my/file.txt';
        $result = Files::pathInfo($path, PATHINFO_FILENAME);

        $this->assertEquals('file', $result);
    }

    #[Test]
    public function pathInfo_handles_multiple_dots_in_filename(): void
    {
        $path = '/path/to/file.name.with.dots.xliff';
        $pathInfo = Files::pathInfo($path);

        $this->assertIsArray($pathInfo);
        $this->assertEquals('file.name.with.dots', $pathInfo['filename']);
        $this->assertEquals('xliff', $pathInfo['extension']);
    }

    #[Test]
    public function pathInfo_handles_combined_flags(): void
    {
        $path = '/path/to/file.txt';
        $result = Files::pathInfo($path, PATHINFO_DIRNAME | PATHINFO_BASENAME);

        $this->assertIsArray($result);
        $this->assertEquals('/path/to', $result['dirname']);
        $this->assertEquals('file.txt', $result['basename']);
        $this->assertArrayNotHasKey('extension', $result);
        $this->assertArrayNotHasKey('filename', $result);
    }

    #[Test]
    public function pathInfo_handles_uppercase_extensions(): void
    {
        $path = '/path/to/file.XLIFF';
        $pathInfo = Files::pathInfo($path);

        $this->assertIsArray($pathInfo);
        $this->assertEquals('xliff', $pathInfo['extension']);
    }

    #[Test]
    public function pathInfo_handles_utf8_characters(): void
    {
        $path = '/path/to/файл.txt';
        $pathInfo = Files::pathInfo($path);

        $this->assertIsArray($pathInfo);
        $this->assertEquals('файл', $pathInfo['filename']);
        $this->assertEquals('txt', $pathInfo['extension']);
    }

    #[Test]
    public function getExtension_returns_extension_in_lowercase(): void
    {
        $this->assertEquals('xliff', Files::getExtension('/path/to/file.xliff'));
        $this->assertEquals('txt', Files::getExtension('/path/to/file.TXT'));
        $this->assertEquals('sdlxliff', Files::getExtension('file.SDLXLIFF'));
    }

    #[Test]
    public function getExtension_returns_null_for_empty_pathinfo(): void
    {
        // Test with a file that results in empty pathinfo
        $this->assertEmpty(Files::getExtension(''));
    }

    #[Test]
    public function getExtension_handles_multiple_dots(): void
    {
        $extension = Files::getExtension('/path/to/file.name.xliff');
        $this->assertEquals('xliff', $extension);
    }

    #[Test]
    public function isXliff_returns_true_for_xliff_extension(): void
    {
        $this->assertTrue(Files::isXliff('/path/to/file.xliff'));
        $this->assertTrue(Files::isXliff('/path/to/file.XLIFF'));
    }

    #[Test]
    public function isXliff_returns_true_for_sdlxliff_extension(): void
    {
        $this->assertTrue(Files::isXliff('/path/to/file.sdlxliff'));
        $this->assertTrue(Files::isXliff('/path/to/file.SDLXLIFF'));
    }

    #[Test]
    public function isXliff_returns_true_for_tmx_extension(): void
    {
        $this->assertTrue(Files::isXliff('/path/to/file.tmx'));
        $this->assertTrue(Files::isXliff('/path/to/file.TMX'));
    }

    #[Test]
    public function isXliff_returns_true_for_xlf_extension(): void
    {
        $this->assertTrue(Files::isXliff('/path/to/file.xlf'));
        $this->assertTrue(Files::isXliff('/path/to/file.XLF'));
    }

    #[Test]
    public function isXliff_returns_false_for_non_xliff_extensions(): void
    {
        $this->assertFalse(Files::isXliff('/path/to/file.xml'));
        $this->assertFalse(Files::isXliff('/path/to/file.txt'));
        $this->assertFalse(Files::isXliff('/path/to/file.doc'));
        $this->assertFalse(Files::isXliff('/path/to/file.json'));
    }


    #[Test]
    public function isXliff_returns_false_when_no_extension(): void
    {
        $this->assertFalse(Files::isXliff('/path/to/filename'));
    }

    #[Test]
    public function getMemoryFileType_returns_tmx_for_tmx_files(): void
    {
        $this->assertEquals('tmx', Files::getMemoryFileType('/path/to/file.tmx'));
        $this->assertEquals('tmx', Files::getMemoryFileType('/path/to/file.TMX'));
    }

    #[Test]
    public function getMemoryFileType_returns_false_for_non_tmx_files(): void
    {
        $this->assertFalse(Files::getMemoryFileType('/path/to/file.xliff'));
        $this->assertFalse(Files::getMemoryFileType('/path/to/file.txt'));
        $this->assertFalse(Files::getMemoryFileType('/path/to/file.xml'));
    }

    #[Test]
    public function getMemoryFileType_returns_false_for_empty_path(): void
    {
        $this->assertFalse(Files::getMemoryFileType(''));
    }

    #[Test]
    public function isTMXFile_returns_true_for_tmx_files(): void
    {
        $this->assertTrue(Files::isTMXFile('/path/to/file.tmx'));
        $this->assertTrue(Files::isTMXFile('/path/to/file.TMX'));
        $this->assertTrue(Files::isTMXFile('memory.tmx'));
    }

    #[Test]
    public function isTMXFile_returns_false_for_non_tmx_files(): void
    {
        $this->assertFalse(Files::isTMXFile('/path/to/file.xliff'));
        $this->assertFalse(Files::isTMXFile('/path/to/file.txt'));
        $this->assertFalse(Files::isTMXFile('/path/to/file.xml'));
        $this->assertFalse(Files::isTMXFile(''));
    }

    #[Test]
    public function isGlossaryFile_always_returns_false(): void
    {
        // This method always returns false as the condition is hardcoded
        $this->assertFalse(Files::isGlossaryFile('/path/to/file.tmx'));
        $this->assertFalse(Files::isGlossaryFile('/path/to/file.xliff'));
        $this->assertFalse(Files::isGlossaryFile('/path/to/file.txt'));
        $this->assertFalse(Files::isGlossaryFile('/path/to/glossary.xml'));
        $this->assertFalse(Files::isGlossaryFile(''));
    }

    #[Test]
    public function pathInfo_handles_windows_paths(): void
    {
        // Test with Windows-style paths
        $path = 'C:\\Users\\Documents\\file.xliff';
        $pathInfo = Files::pathInfo($path);

        $this->assertIsArray($pathInfo);
        $this->assertArrayHasKey('basename', $pathInfo);
        $this->assertArrayHasKey('extension', $pathInfo);
    }

    #[Test]
    public function pathInfo_handles_file_without_extension(): void
    {
        $path = '/path/to/filename';
        $pathInfo = Files::pathInfo($path);

        $this->assertIsArray($pathInfo);
        $this->assertEquals('filename', $pathInfo['basename']);
        // When there's no dot, extension is empty and filename is the basename
        $this->assertEquals('', $pathInfo['extension']);
        $this->assertEquals('filename', $pathInfo['filename']);
    }

    #[Test]
    public function pathInfo_handles_single_filename(): void
    {
        $path = 'file.txt';
        $pathInfo = Files::pathInfo($path);

        $this->assertIsArray($pathInfo);
        $this->assertEquals('', $pathInfo['dirname']);
        $this->assertEquals('file.txt', $pathInfo['basename']);
        $this->assertEquals('txt', $pathInfo['extension']);
        $this->assertEquals('file', $pathInfo['filename']);
    }

    #[Test]
    public function pathInfo_handles_hidden_files(): void
    {
        $path = '/path/to/.hidden';
        $pathInfo = Files::pathInfo($path);

        $this->assertIsArray($pathInfo);
        $this->assertEquals('hidden', $pathInfo['extension']);
        $this->assertEquals('', $pathInfo['filename']);
        $this->assertEquals('.hidden', $pathInfo['basename']);
    }

    #[Test]
    public function pathInfo_returns_single_value_when_one_flag(): void
    {
        $path = '/path/to/file.txt';

        $dirname = Files::pathInfo($path, PATHINFO_DIRNAME);
        $this->assertTrue(is_string($dirname));
        $this->assertEquals('/path/to', $dirname);

        $basename = Files::pathInfo($path, PATHINFO_BASENAME);
        $this->assertTrue(is_string($basename));
        $this->assertEquals('file.txt', $basename);

        $extension = Files::pathInfo($path, PATHINFO_EXTENSION);
        $this->assertTrue(is_string($extension));
        $this->assertEquals('txt', $extension);

        $filename = Files::pathInfo($path, PATHINFO_FILENAME);
        $this->assertTrue(is_string($filename));
        $this->assertEquals('file', $filename);
    }

    #[Test]
    public function getExtension_handles_edge_cases(): void
    {
        // File with only extension
        $this->assertEquals('txt', Files::getExtension('.txt'));

        // File with trailing dot
        $this->assertEquals('', Files::getExtension('file.'));
    }

    #[Test]
    public function all_methods_work_together(): void
    {
        $xliffPath = '/project/translations/document.xliff';

        // Test the full workflow
        $this->assertTrue(Files::isXliff($xliffPath));
        $this->assertEquals('xliff', Files::getExtension($xliffPath));
        $this->assertFalse(Files::isTMXFile($xliffPath));
        $this->assertFalse(Files::isGlossaryFile($xliffPath));
        $this->assertFalse(Files::getMemoryFileType($xliffPath));

        $tmxPath = '/project/memory.tmx';

        $this->assertTrue(Files::isXliff($tmxPath));
        $this->assertEquals('tmx', Files::getExtension($tmxPath));
        $this->assertTrue(Files::isTMXFile($tmxPath));
        $this->assertFalse(Files::isGlossaryFile($tmxPath));
        $this->assertEquals('tmx', Files::getMemoryFileType($tmxPath));
    }

    /**
     * Test isXliff returns false when path is null (covers line 92)
     */
    #[Test]
    public function isXliff_returns_false_for_null_path(): void
    {
        $this->assertFalse(Files::isXliff(null));
    }
}
