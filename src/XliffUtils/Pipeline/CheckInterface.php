<?php

namespace Matecat\XliffParser\XliffUtils\Pipeline;

interface CheckInterface
{
    /**
     * @param string $tmp
     *
     * @return array|null
     */
    public function check($tmp);
}
