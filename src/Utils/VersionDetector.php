<?php

namespace Matecat\XliffParser\Utils;

use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;

class VersionDetector
{
    /**
     * @var array
     */
    private $versions_1 = ['1.0', '1.1','1.2'];

    /**
     * @var array
     */
    private $versions_2 = ['2.0'];

    /**
     * @param string $xliffContent
     *
     * @return int
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    public function detect($xliffContent)
    {
        preg_match( '|<xliff.*?\sversion\s?=\s?["\'](.*?)["\']|si', substr( $xliffContent, 0, 1000 ), $versionMatches );
        preg_match( '|<xliff.*?\sxmlns\s?=\s?["\']urn:oasis:names:tc:xliff:document:(.*?)["\']|si', substr( $xliffContent, 0, 1000 ), $xmlnsMatches );

        if(empty($versionMatches) or empty($xmlnsMatches)){
            throw new NotValidFileException('This is not a valid xliff file');
        }

        $version = $versionMatches[1];
        $xmlns = $xmlnsMatches[1];

        if($version != $xmlns){
            throw new NotSupportedVersionException('The xmlns and version declaration on root node do not correspond');
        }

        if(in_array($version, $this->versions_1)){
            return 1;
        }

        if(in_array($version, $this->versions_2)){
            return 2;
        }

        throw new NotSupportedVersionException('Not supported version');
    }
}