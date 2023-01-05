<?php

namespace Matecat\XliffParser\XliffReplacer;

interface XliffReplacerCallbackInterface {
    /**
     * @param int    $segmentId
     * @param string $segment
     * @param string $translation
     * @param array  $dataRefMap
     *
     * @return bool
     */
    public function thereAreErrors( $segmentId, $segment, $translation, array $dataRefMap = [] );
}
