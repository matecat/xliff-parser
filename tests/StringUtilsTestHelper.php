<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 06/08/24
 * Time: 12:39
 *
 */

namespace Matecat\XliffParser\Tests;

class StringUtilsTestHelper {

    /**
     * @param string $needle
     * @param string $haystack
     *
     * @return bool
     */
    public static function contains( $needle, $haystack ) {
        return mb_strpos( $haystack, $needle ) !== false;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function htmlentities( $string ) {
        return htmlentities( $string, ENT_NOQUOTES );
    }

    /**
     * @param string $str
     *
     * @return bool
     */
    public static function isAnEscapedHTML( $str ) {
        return preg_match( '#/[a-z]*&gt;#i', $str ) != 0;
    }

    /**
     * Get the last character of a string
     *
     * @param $string
     *
     * @return string
     */
    public static function lastChar( $string ) {
        return mb_substr( $string, -1 );
    }

    /**
     * @TODO We need to improve this
     *
     * @param string $string
     *
     * @return bool
     */
    public static function isHtmlString( $string ) {
        $string = stripslashes( $string );

        if ( $string === '<>' ) {
            return false;
        }

        preg_match( "#</?[a-zA-Z1-6-]+((\s+[a-zA-Z1-6-]+(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)/?>#", $string, $matches );

        return count( $matches ) !== 0;
    }

}