<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffReplacer;

interface XliffReplacerCallbackInterface
{

    /**
     * Check if there are errors in the translation.
     *
     * @param int $segmentId Segment ID
     * @param string $segment Source segment
     * @param string $translation Translation text
     * @param array<string, string>|null $dataRefMap Data reference map
     * @param string|null $error Error message
     *
     * @return bool True if there are errors
     */
    public function thereAreErrors(
        int $segmentId,
        string $segment,
        string $translation,
        ?array $dataRefMap = [],
        ?string $error = null
    ): bool;
}
