<?php

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

class CheckXliffVersion2 implements CheckInterface {
    /**
     * @param array|null $tmp
     *
     * @return array|null
     */
    public function check( ?array $tmp = [] ): ?array {
        $fileType = [];

        if ( isset( $tmp[ 0 ] ) ) {
            preg_match( '|<xliff.*?\sversion\s?=\s?["\'](.*?)["\']|si', substr( $tmp[ 0 ], 0, 1000 ), $versionMatches );
            preg_match( '|<xliff.*?\sxmlns\s?=\s?["\']urn:oasis:names:tc:xliff:document:(.*?)["\']|si', substr( $tmp[ 0 ], 0, 1000 ), $xmlnsMatches );

            if ( !empty( $versionMatches ) && !empty( $xmlnsMatches ) ) {
                $version = $versionMatches[ 1 ];
                $xmlns   = $xmlnsMatches[ 1 ];

                if ( $version === $xmlns && $version >= 2 ) {
                    $fileType[ 'proprietary' ]            = false;
                    $fileType[ 'proprietary_name' ]       = 'Xliff v' . $version . ' File';
                    $fileType[ 'proprietary_short_name' ] = 'xliff_v2';
                    $fileType[ 'converter_version' ]      = '2.0';

                    return $fileType;
                }
            }
        }

        return null;
    }
}
