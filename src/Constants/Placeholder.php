<?php

namespace Matecat\XliffParser\Constants;

class Placeholder {
    /**
     * Placeholder map to preserve white spaces
     * contained in <originalData> map
     * (only for Xliff 2.0)
     */
    const WHITE_SPACE_PLACEHOLDER = '###___WHITE_SPACE_PLACEHOLDER___###';
    const NEW_LINE_PLACEHOLDER    = '###___NEW_LINE_PLACEHOLDER___###';
    const TAB_PLACEHOLDER         = '###___TAB_PLACEHOLDER___###';
    const LT_PLACEHOLDER          = '###___LT_PLACEHOLDER___###';
    const GT_PLACEHOLDER          = '###___GT_PLACEHOLDER___###';

    /**
     * Placeholder map to preserve empty xml tags (like <pc ...></pc>)
     * (only for Xliff 2.0)
     */
    const EMPTY_TAG_PLACEHOLDER = '###___EMPTY_TAG_PLACEHOLDER___###';
}