<?php

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

class CheckGlobalSight implements CheckInterface {
    /**
     * @param array|null $tmp
     *
     * @return array|null
     */
    public function check( ?array $tmp = [] ): ?array {
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

        return null;

    }
}
