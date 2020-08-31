<?php

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\Utils\Files;

class XliffProprietaryDetect
{
    /**
     * @var array
     */
    protected static $fileType = [];

    /**
     * @param $fullPathToFile
     *
     * @return array
     */
    public static function getInfo( $fullPathToFile )
    {
        self::reset();
        $tmp = self::getFirst1024CharsFromXliff( null, $fullPathToFile );
        self::$fileType['info'] = Files::pathInfo( $fullPathToFile );
        self::checkSDL( $tmp );
        self::checkGlobalSight( $tmp );
        self::checkMateCATConverter( $tmp );

        return self::$fileType;
    }

    /**
     * Reset $fileType
     */
    private static function reset() {
        self::$fileType = [
            'info'                   => [],
            'proprietary'            => false,
            'proprietary_name'       => null,
            'proprietary_short_name' => null
        ];
    }

    /**
     * @param null $stringData
     * @param null $fullPathToFile
     *
     * @return array|bool
     */
    private static function getFirst1024CharsFromXliff( $stringData = null, $fullPathToFile = null )
    {
        if ( !empty ( $stringData ) and empty( $fullPathToFile ) ) {
            $pathInfo = [];
            $stringData = substr( $stringData, 0, 1024 );
        } elseif ( empty( $stringData ) and !empty( $fullPathToFile ) ) {
            $pathInfo = Files::pathInfo( $fullPathToFile );

            if ( is_file( $fullPathToFile ) ) {
                $file_pointer = fopen( "$fullPathToFile", 'r' );
                // Checking Requirements (By specs, I know that xliff version is in the first 1KB)
                $stringData = fread( $file_pointer, 1024 );
                fclose( $file_pointer );
            }
        } elseif ( !empty( $stringData ) && !empty( $fullPathToFile ) ) {
            $pathInfo = Files::pathInfo( $fullPathToFile );
        }

        if ( !empty( $pathInfo ) and !Files::isXliff($fullPathToFile) ) {
            return false;
        }

        if ( !empty( $stringData ) ) {
            return array( $stringData );
        }

        return false;
    }

    /**
     * @param $tmp
     */
    private static function checkSDL( $tmp )
    {
        if ( isset( $tmp[ 0 ] ) ) {
            if ( stripos( $tmp[ 0 ], 'sdl:version' ) !== false ) {
                //little trick, we consider not proprietary Sdlxliff files because we can handle them
                self::$fileType[ 'proprietary' ]            = false;
                self::$fileType[ 'proprietary_name' ]       = 'SDL Studio ';
                self::$fileType[ 'proprietary_short_name' ] = 'trados';
                self::$fileType[ 'converter_version' ]      = 'legacy';
            }
        }

    }

    /**
     * @param $tmp
     */
    private static function checkGlobalSight( $tmp )
    {
        if ( isset( $tmp[ 0 ] ) ) {
            if ( stripos( $tmp[ 0 ], 'globalsight' ) !== false ) {
                self::$fileType[ 'proprietary' ]            = true;
                self::$fileType[ 'proprietary_name' ]       = 'GlobalSight Download File';
                self::$fileType[ 'proprietary_short_name' ] = 'globalsight';
                self::$fileType[ 'converter_version' ]      = 'legacy';
            }
        }
    }

    /**
     * @param $tmp
     */
    private static function checkMateCATConverter( $tmp )
    {
        if ( isset( $tmp[ 0 ] ) ) {
            preg_match( '#tool-id\s*=\s*"matecat-converter(\s+([^"]+))?"#i', $tmp[ 0 ], $matches );
            if ( !empty( $matches ) ) {
                self::$fileType[ 'proprietary' ]            = false;
                self::$fileType[ 'proprietary_name' ]       = 'MateCAT Converter';
                self::$fileType[ 'proprietary_short_name' ] = 'matecat_converter';
                if ( isset($matches[ 2 ]) ) {
                    self::$fileType[ 'converter_version' ] = $matches[ 2 ];
                } else {
                    // First converter release didn't specify version
                    self::$fileType[ 'converter_version' ] = '1.0';
                }
            }
        }
    }

    /**
     * @param $stringData
     *
     * @return array
     */
    public static function getInfoByStringData( $stringData )
    {
        self::reset();

        $tmp = self::getFirst1024CharsFromXliff( $stringData );
        self::$fileType['info'] = [];
        self::checkSDL( $tmp );
        self::checkGlobalSight( $tmp );
        self::checkMateCATConverter( $tmp );

        return self::$fileType;
    }
}