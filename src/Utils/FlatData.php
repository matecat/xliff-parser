<?php

namespace Matecat\XliffParser\Utils;

/**
 * This class was taken from:
 *
 * https://stackoverflow.com/questions/11427398/how-to-implode-array-with-key-and-value-without-foreach-in-php
 */
class FlatData {
    public static function flatArray( array $input = [], $separator_elements = ', ', $separator = ': ' ) {
        return implode( $separator_elements, array_map(
                function ( $v, $k, $s ) {
                    return sprintf( "%s{$s}\"%s\"", $k, $v );
                },
                $input,
                array_keys( $input ),
                array_fill( 0, count( $input ), $separator )
        ) );
    }
}
