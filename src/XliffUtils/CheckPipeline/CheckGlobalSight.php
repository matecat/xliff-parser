<?php

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

class CheckGlobalSight implements CheckInterface {
    /**
     * @param string $tmp
     *
     * @return array|null
     */
    public function check( $tmp ) {
        $fileType = [];

        if ( isset( $tmp[ 0 ] ) ) {
            if ( stripos( $tmp[ 0 ], 'globalsight' ) !== false ) {
                $fileType[ 'proprietary' ]            = true;
                $fileType[ 'proprietary_name' ]       = 'GlobalSight Download File';
                $fileType[ 'proprietary_short_name' ] = 'globalsight';
                $fileType[ 'converter_version' ]      = 'legacy';

                return $fileType;
            }
        }
    }
}
