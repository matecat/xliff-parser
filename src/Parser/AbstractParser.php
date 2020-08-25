<?php

namespace Matecat\XliffParser\Parser;

abstract class AbstractParser
{
    /**
     * @param \DOMDocument $dom
     *
     * @return array
     */
    abstract public function parse(\DOMDocument $dom, $output = []);

    /**
     * Get <file> node(s) content
     *
     * @param string $xliffContent
     *
     * @return array|false|string[]
     */
    protected function getFiles( $xliffContent)
    {
        return preg_split( '|<file[\s>]|si', $xliffContent, -1, PREG_SPLIT_NO_EMPTY );
    }

    /**
     * Getting <file> Attributes
     * Restrict preg action for speed, just for attributes
     *
     * @param string $file
     *
     * @return false|string
     */
    protected function getFileAttributes($file)
    {
        return substr( $file, 0, strpos( $file, '>' ) + 1 );
    }
}