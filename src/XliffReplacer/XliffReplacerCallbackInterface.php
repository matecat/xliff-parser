<?php

namespace Matecat\XliffParser\XliffReplacer;

interface XliffReplacerCallbackInterface
{
    /**
     * @param string $segment
     * @param string $translation
     * @param array  $dataRefMap
     *
     * @return bool
     */
    public function thereAreErrors($segment, $translation, array $dataRefMap = []);
}
