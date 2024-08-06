<?php

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

interface CheckInterface {
    /**
     * @param array|null $tmp
     *
     * @return array|null
     */
    public function check( ?array $tmp = [] ): ?array;
}
