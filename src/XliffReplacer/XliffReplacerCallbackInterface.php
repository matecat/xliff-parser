<?php

namespace Matecat\XliffParser\XliffReplacer;

interface XliffReplacerCallbackInterface {
    /**
     * @param int         $segmentId
     * @param string      $segment
     * @param string      $translation
     * @param array|null  $dataRefMap
     * @param string|null $error
     *
     * @return bool
     */
    public function thereAreErrors( int $segmentId, string $segment, string $translation, ?array $dataRefMap = [], ?string $error = null ): bool;
}
