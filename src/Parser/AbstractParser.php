<?php

namespace Matecat\XliffParser\Parser;

abstract class AbstractParser
{
    /**
     * @param string $xliffContent
     *
     * @return array
     */
    abstract public function parse($xliffContent);

    /**
     * Pre-Processing.
     * Fixing non UTF-8 encoding (often I get Unicode UTF-16)
     *
     * @param $xliffContent
     * @param $xliff
     *
     * @return false|string
     */
    protected function forceUft8Encoding($xliffContent, &$xliff)
    {
        $enc = mb_detect_encoding( $xliffContent );

        if ( $enc !== 'UTF-8' ) {
            $xliff[ 'parser-warnings' ][] = "Input identified as $enc ans converted UTF-8. May not be a problem if the content is English only";

            return iconv( $enc, 'UTF-8', $xliffContent );
        }

        return $xliffContent;
    }

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