<?php

namespace Matecat\XliffParser\XliffReplacer;

interface XliffReplacerCallbackInterface {
    /**
     * @param int $segmentId
     * @param string $segment
     * @param string $translation
     * @param array $dataRefMap
     * @param null $error
     * @return bool
     */
    public function thereAreErrors( $segmentId, $segment, $translation, array $dataRefMap = [], $error = null );
}
