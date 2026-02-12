<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

interface CheckInterface {

    /**
     * Check XLIFF content for proprietary format markers.
     *
     * @param array<int, string>|null $tmp First 1024 chars of XLIFF content
     *
     * @return array{proprietary: bool, proprietary_name: string, proprietary_short_name: string, converter_version: string}|null
     */
    public function check( ?array $tmp = [] ): ?array;
}
