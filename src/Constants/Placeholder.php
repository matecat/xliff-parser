<?php

namespace Matecat\XliffParser\Constants;

class Placeholder
{
    /**
     * Placeholder map to preserve white spaces
     * contained in <original-data> map
     * (only for Xliff 2.0)
     */
    const WHITE_SPACE_PLACEHOLDER = '###___WHITE_SPACE_PLACEHOLDER___###';
    const NEW_LINE_PLACEHOLDER = '###___NEW_LINE_PLACEHOLDER___###';
    const TAB_PLACEHOLDER = '###___TAB_PLACEHOLDER___###';
}