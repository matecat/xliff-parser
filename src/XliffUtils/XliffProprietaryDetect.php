<?php

namespace Matecat\XliffParser\XliffUtils;

use Exception;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\Utils\Files;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckGlobalSight;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckMateCATConverter;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckSDL;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckXliffVersion2;

class XliffProprietaryDetect {
    /**
     * @var array
     */
    protected static array $fileType = [];

    /**
     * @param string $xliffContent
     *
     * @return array
     */
    public static function getInfoFromXliffContent( string $xliffContent ): array {
        self::reset();
        $tmp = self::getFirst1024CharsFromXliff( $xliffContent, null );

        return self::getInfoFromTmp( $tmp );
    }

    /**
     * @param string $fullPathToFile
     *
     * @return array
     */
    public static function getInfo( string $fullPathToFile ): array {
        self::reset();
        $tmp                      = self::getFirst1024CharsFromXliff( null, $fullPathToFile );
        self::$fileType[ 'info' ] = Files::pathInfo( $fullPathToFile );

        return self::getInfoFromTmp( $tmp );
    }

    /**
     * @param array|null $tmp
     *
     * @return array
     */
    private static function getInfoFromTmp( ?array $tmp = [] ): array {
        try {
            self::checkVersion( $tmp );
        } catch ( Exception $ignore ) {
            // do nothing
            // self::$fileType[ 'version' ] is left empty
        }

        // run CheckXliffProprietaryPipeline
        $pipeline = self::runPipeline( $tmp );

        self::$fileType[ 'proprietary' ]            = $pipeline[ 'proprietary' ];
        self::$fileType[ 'proprietary_name' ]       = $pipeline[ 'proprietary_name' ];
        self::$fileType[ 'proprietary_short_name' ] = $pipeline[ 'proprietary_short_name' ];
        self::$fileType[ 'converter_version' ]      = $pipeline[ 'converter_version' ];

        return self::$fileType;
    }

    /**
     * @param array|null $tmp
     *
     * @return array
     */
    private static function runPipeline( ?array $tmp = [] ): array {
        $pipeline = new CheckXliffProprietaryPipeline( $tmp );
        $pipeline->addCheck( new CheckSDL() );
        $pipeline->addCheck( new CheckGlobalSight() );
        $pipeline->addCheck( new CheckMateCATConverter() );
        $pipeline->addCheck( new CheckXliffVersion2() );

        return $pipeline->run();
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
     * @param string|null $stringData
     * @param string|null $fullPathToFile
     *
     * @return ?array
     */
    private static function getFirst1024CharsFromXliff( ?string $stringData = null, string $fullPathToFile = null ): ?array {
        if ( !empty( $stringData ) && empty( $fullPathToFile ) ) {
            $pathInfo   = [];
            $stringData = substr( $stringData, 0, 1024 );
        } elseif ( empty( $stringData ) && !empty( $fullPathToFile ) ) {
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

        if ( !empty( $pathInfo ) && !Files::isXliff( $fullPathToFile ) ) {
            return null;
        }

        if ( !empty( $stringData ) ) {
            return [ $stringData ];
        }

        return null;
    }

    /**
     * @param $tmp
     *
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    protected static function checkVersion( $tmp ) {
        if ( isset( $tmp[ 0 ] ) ) {
            self::$fileType[ 'version' ] = XliffVersionDetector::detect( $tmp[ 0 ] );
        }
    }

    /**
     * @param string $stringData
     *
     * @return array
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    public static function getInfoByStringData( string $stringData ): array {
        self::reset();

        $tmp                      = self::getFirst1024CharsFromXliff( $stringData );
        self::$fileType[ 'info' ] = [];
        self::checkVersion( $tmp );

        // run CheckXliffProprietaryPipeline
        $pipeline = self::runPipeline( $tmp );

        self::$fileType[ 'proprietary' ]            = $pipeline[ 'proprietary' ];
        self::$fileType[ 'proprietary_name' ]       = $pipeline[ 'proprietary_name' ];
        self::$fileType[ 'proprietary_short_name' ] = $pipeline[ 'proprietary_short_name' ];
        self::$fileType[ 'converter_version' ]      = $pipeline[ 'converter_version' ];

        return self::$fileType;
    }

    /**
     * @param string      $fullPath
     * @param boolean     $enforceOnXliff
     * @param string|null $filterAddress
     *
     * @return bool|int
     */
    public static function fileMustBeConverted( string $fullPath, ?bool $enforceOnXliff = false, ?string $filterAddress = null ) {
        $convert = true;

        $fileType       = self::getInfo( $fullPath );
        $memoryFileType = Files::getMemoryFileType( $fullPath );

        if ( Files::isXliff( $fullPath ) || $memoryFileType ) {
            if ( !empty( $filterAddress ) ) {

                //conversion enforce
                if ( !$enforceOnXliff ) {

                    //if file is not proprietary AND Enforce is disabled
                    //we take it as is
                    if ( !$fileType[ 'proprietary' ] || $memoryFileType ) {
                        $convert = false;
                        //ok don't convert a standard sdlxliff
                    }
                } else {
                    //if conversion enforce is active
                    //we force all xliff files but not files produced by SDL Studio because we can handle them
                    if (
                            $fileType[ 'proprietary_short_name' ] == 'matecat_converter'
                            || $fileType[ 'proprietary_short_name' ] == 'trados'
                            || $fileType[ 'proprietary_short_name' ] == 'xliff_v2'
                            || $memoryFileType
                    ) {
                        $convert = false;
                        //ok don't convert a standard sdlxliff
                    }
                }
            } elseif ( $fileType[ 'proprietary' ] ) {

                /**
                 * Application misconfiguration.
                 * upload should not be happened, but if we are here, raise an error.
                 * @see upload.class.php
                 * */

                $convert = -1;
                //stop execution
            } else {
                $convert = false;
                //ok don't convert a standard sdlxliff
            }
        }

        return $convert;
    }
}
