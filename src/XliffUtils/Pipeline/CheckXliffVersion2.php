<?php

namespace Matecat\XliffParser\XliffUtils\Pipeline;

use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;

class CheckXliffVersion2 implements CheckInterface
{
    /**
     * @param string $tmp
     *
     * @return array|void|null
     */
    public function check($tmp)
    {
        $fileType = [];

        if (isset($tmp[ 0 ])) {
            preg_match('|<xliff.*?\sversion\s?=\s?["\'](.*?)["\']|si', substr($tmp[0], 0, 1000), $versionMatches);
            preg_match('|<xliff.*?\sxmlns\s?=\s?["\']urn:oasis:names:tc:xliff:document:(.*?)["\']|si', substr($tmp[0], 0, 1000), $xmlnsMatches);

            if (!empty($versionMatches) and !empty($xmlnsMatches)) {
                $version = $versionMatches[1];
                $xmlns = $xmlnsMatches[1];

                if ($version === $xmlns and $version >= 2) {
                    $fileType[ 'proprietary' ]            = true;
                    $fileType[ 'proprietary_name' ]       = 'Xliff v'.$version.' File';
                    $fileType[ 'proprietary_short_name' ] = 'xliff_v2';
                    $fileType[ 'converter_version' ]      = '2.0';

                    return $fileType;
                }
            }
        }
    }
}
