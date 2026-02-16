<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;

final class XliffVersionDetector
{
    /** @var array<int, string> */
    private const array VERSIONS_1 = ['1.0', '1.1', '1.2'];

    /** @var array<int, string> */
    private const array VERSIONS_2 = ['2.0', '2.1'];

    /**
     * Detect XLIFF version from content.
     *
     * @param string $xliffContent The XLIFF content to analyze
     *
     * @return int Version number (1 or 2)
     *
     * @throws NotSupportedVersionException If version is not supported
     * @throws NotValidFileException If content is not valid XLIFF
     */
    public static function detect(string $xliffContent): int
    {
        preg_match('|<xliff.*?\sversion\s?=\s?["\'](.*?)["\']|si', substr($xliffContent, 0, 1000), $versionMatches);

        if (empty($versionMatches)) {
            throw new NotValidFileException('This is not a valid xliff file');
        }

        return self::resolveVersion($versionMatches[1]);
    }

    /**
     * Resolve version string to version number.
     *
     * @throws NotSupportedVersionException If version is not supported
     */
    private static function resolveVersion(string $version): int
    {
        if (in_array($version, self::VERSIONS_1, true)) {
            return 1;
        }

        if (in_array($version, self::VERSIONS_2, true)) {
            return 2;
        }

        throw new NotSupportedVersionException('Not supported version');
    }
}
