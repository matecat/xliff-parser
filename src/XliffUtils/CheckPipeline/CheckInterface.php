<?php

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

interface CheckInterface
{
    /**
     * @param string $tmp
     *
     * @return array|null
     */
    public function check($tmp);
}
