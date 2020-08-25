<?php

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;

class VersionDetector
{
    /**
     * @var array
     */
    private static $versions_1 = ['1.0', '1.1','1.2'];

    /**
     * @var array
     */
    private static $versions_2 = ['2.0'];

    /**
     * @param string $xliffContent
     *
     * @return int
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    public static function detect($xliffContent)
    {
        preg_match('|<xliff.*?\sversion\s?=\s?["\'](.*?)["\']|si', substr($xliffContent, 0, 1000), $versionMatches);
        preg_match('|<xliff.*?\sxmlns\s?=\s?["\']urn:oasis:names:tc:xliff:document:(.*?)["\']|si', substr($xliffContent, 0, 1000), $xmlnsMatches);

        if (empty($versionMatches) or empty($xmlnsMatches)) {
            throw new NotValidFileException('This is not a valid xliff file');
        }

        $version = $versionMatches[1];
        $xmlns = $xmlnsMatches[1];

        if ($version != $xmlns) {
            throw new NotSupportedVersionException('The xmlns and version declaration on root node do not correspond');
        }

        return self::resolveVersion($version);
    }

    /**
     * @param string $version
     *
     * @return int
     * @throws NotSupportedVersionException
     */
    private static function resolveVersion($version)
    {
        if (in_array($version, self::$versions_1)) {
            return 1;
        }

        if (in_array($version, self::$versions_2)) {
            return 2;
        }

        throw new NotSupportedVersionException('Not supported version');
    }
}
