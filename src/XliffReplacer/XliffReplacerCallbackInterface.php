<?php

namespace Matecat\XliffParser\XliffReplacer;

interface XliffReplacerCallbackInterface
{
    /**
     * @param string $segment
     * @param string $translation
     *
     * @return bool
     */
    public function thereAreErrors($segment, $translation);
}
