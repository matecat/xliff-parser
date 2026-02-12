<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Utils;

/**
 * File path utility class with UTF-8 aware path parsing.
 *
 * PHP's native pathinfo() is not UTF-8 aware, so this class provides
 * UTF-8 compatible alternatives for file path operations.
 */
final readonly class Files
{
    /**
     * UTF-8 aware pathinfo implementation.
     *
     * Returns array with complete info about a path:
     * [
     *    'dirname'   => PATHINFO_DIRNAME,
     *    'basename'  => PATHINFO_BASENAME,
     *    'extension' => PATHINFO_EXTENSION,
     *    'filename'  => PATHINFO_FILENAME
     * ]
     *
     * @param string $path The file path to parse
     * @param int $options Bitwise OR of PATHINFO_* constants (default: all components)
     *
     * @return array<string, string>|string Array of path components or single component string
     */
    public static function pathInfo(
        string $path,
        int $options = PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME
    ): array|string {
        $rawPath = explode(DIRECTORY_SEPARATOR, $path);

        $basename = array_pop($rawPath);
        $dirname = implode(DIRECTORY_SEPARATOR, $rawPath);

        $explodedFileName = explode('.', $basename);

        $extension = count($explodedFileName) > 1 ? strtolower(array_pop($explodedFileName)) : '';
        $filename = implode('.', $explodedFileName);

        $returnArray = [];

        $components = [
            'dirname' => ['flag' => PATHINFO_DIRNAME, 'value' => $dirname],
            'basename' => ['flag' => PATHINFO_BASENAME, 'value' => $basename],
            'extension' => ['flag' => PATHINFO_EXTENSION, 'value' => $extension],
            'filename' => ['flag' => PATHINFO_FILENAME, 'value' => $filename],
        ];

        // Add requested components to return array based on options bitfield
        foreach ($components as $field => $component) {
            // Binary AND - check if this flag is requested
            if (($options & $component['flag']) > 0) {
                $returnArray[$field] = $component['value'];
            }
        }

        // If only one component requested, return string instead of array
        return count($returnArray) === 1 ? array_pop($returnArray) : $returnArray;
    }

    /**
     * Get file extension in lowercase.
     *
     * @param string $path The file path
     *
     * @return string The file extension in lowercase (empty string if no extension)
     */
    public static function getExtension(string $path): string
    {
        $extension = self::pathInfo($path, PATHINFO_EXTENSION);

        return is_string($extension) ? strtolower($extension) : '';
    }

    /**
     * Check if file is an XLIFF-compatible format.
     *
     * Supported extensions: xliff, sdlxliff, tmx, xlf
     *
     * @param string|null $path The file path to check
     *
     * @return bool True if file is XLIFF-compatible format
     */
    public static function isXliff(?string $path): bool
    {
        if ($path === null) {
            return false;
        }

        $extension = self::getExtension($path);

        if (empty($extension)) {
            return false;
        }

        return match ($extension) {
            'xliff', 'sdlxliff', 'tmx', 'xlf' => true,
            default => false,
        };
    }

    /**
     * Get memory file type based on extension.
     *
     * @param string $path The file path
     *
     * @return string|false 'tmx' for TMX files, false otherwise
     */
    public static function getMemoryFileType(string $path): string|false
    {
        $extension = self::pathInfo($path, PATHINFO_EXTENSION);

        if (!is_string($extension)) {
            return false;
        }

        return match (strtolower($extension)) {
            'tmx' => 'tmx',
            default => false,
        };
    }

    /**
     * Check if file is a TMX (Translation Memory eXchange) file.
     *
     * @param string $path The file path
     *
     * @return bool True if file has .tmx extension
     */
    public static function isTMXFile(string $path): bool
    {
        return self::getMemoryFileType($path) === 'tmx';
    }

    /**
     * Check if file is a glossary file.
     *
     * Note: This is a stub method that always returns false.
     * Glossary file detection is not currently implemented.
     *
     * @param string $path The file path
     *
     * @return bool Always returns false (not implemented)
     */
    public static function isGlossaryFile(string $path): bool
    {
        return self::getMemoryFileType($path) === 'glossary'; // Always false - not implemented
    }

}
