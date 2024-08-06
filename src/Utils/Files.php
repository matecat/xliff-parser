<?php

namespace Matecat\XliffParser\Utils;

class Files {
    /**
     * PHP Pathinfo is not UTF-8 aware, so we rewrite it.
     * It returns array with complete info about a path
     * [
     *    'dirname'   => PATHINFO_DIRNAME,
     *    'basename'  => PATHINFO_BASENAME,
     *    'extension' => PATHINFO_EXTENSION,
     *    'filename'  => PATHINFO_FILENAME
     * ]
     *
     * @param string   $path
     * @param int|null $options
     *
     * @return array|mixed
     */
    public static function pathInfo( string $path, ?int $options = 15 ) {
        $rawPath = explode( DIRECTORY_SEPARATOR, $path );

        $basename = array_pop( $rawPath );
        $dirname  = implode( DIRECTORY_SEPARATOR, $rawPath );

        $explodedFileName = explode( ".", $basename );
        $extension        = strtolower( array_pop( $explodedFileName ) );
        $filename         = implode( ".", $explodedFileName );

        $returnArray = [];

        $flagMap = [
                'dirname'   => PATHINFO_DIRNAME,
                'basename'  => PATHINFO_BASENAME,
                'extension' => PATHINFO_EXTENSION,
                'filename'  => PATHINFO_FILENAME
        ];

        // foreach flag, add in $return_array the corresponding field,
        // obtained by variable name correspondence
        foreach ( $flagMap as $field => $i ) {
            //binary AND
            if ( ( $options & $i ) > 0 ) {
                //variable substitution: $field can be one between 'dirname', 'basename', 'extension', 'filename'
                // $$field gets the value of the variable named $field
                $returnArray[ $field ] = $$field;
            }
        }

        if ( count( $returnArray ) == 1 ) {
            $returnArray = array_pop( $returnArray );
        }

        return $returnArray;
    }

    /**
     * @param $path
     *
     * @return ?string
     */
    public static function getExtension( $path ): ?string {
        $pathInfo = self::pathInfo( $path );

        if ( empty( $pathInfo ) ) {
            return null;
        }

        return strtolower( $pathInfo[ 'extension' ] );
    }

    /**
     * @param string|null $path
     *
     * @return bool
     */
    public static function isXliff( ?string $path ): bool {
        $extension = self::getExtension( $path );

        if ( !$extension ) {
            return false;
        }

        switch ( $extension ) {
            case 'xliff':
            case 'sdlxliff':
            case 'tmx':
            case 'xlf':
                return true;
            default:
                return false;
        }
    }

    /**
     * @param string $path
     *
     * @return bool|string
     */
    public static function getMemoryFileType( string $path ) {
        $pathInfo = self::pathInfo( $path );

        if ( empty( $pathInfo ) ) {
            return false;
        }

        switch ( strtolower( $pathInfo[ 'extension' ] ) ) {
            case 'tmx':
                return 'tmx';
            case 'g':
                return 'glossary';
            default:
                return false;
        }
    }

    /**
     * @param $path
     *
     * @return bool
     */
    public static function isTMXFile( $path ): bool {
        return self::getMemoryFileType( $path ) === 'tmx';
    }

    /**
     * @param $path
     *
     * @return bool
     */
    public static function isGlossaryFile( $path ): bool {
        return self::getMemoryFileType( $path ) === 'glossary';
    }
}
