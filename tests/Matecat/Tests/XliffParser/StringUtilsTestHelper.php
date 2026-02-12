<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

final class StringUtilsTestHelper {

    public static function contains( string $needle, string $haystack ): bool {
        return mb_strpos( $haystack, $needle ) !== false;
    }

    public static function htmlentities( string $string ): string {
        return htmlentities( $string, ENT_NOQUOTES );
    }

    public static function isAnEscapedHTML( string $str ): bool {
        return preg_match( '#/[a-z]*&gt;#i', $str ) !== 0;
    }

    /**
     * Get the last character of a string.
     */
    public static function lastChar( string $string ): string {
        return mb_substr( $string, -1 );
    }

    public static function isHtmlString( string $string ): bool {
        $string = stripslashes( $string );

        if ( $string === '<>' ) {
            return false;
        }

        preg_match( "#</?[a-zA-Z1-6-]+((\s+[a-zA-Z1-6-]+(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)/?>#", $string, $matches );

        return count( $matches ) !== 0;
    }

}