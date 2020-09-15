<?php

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\Utils\Files;
use Matecat\XliffParser\XliffUtils\Pipeline\CheckGlobalSight;
use Matecat\XliffParser\XliffUtils\Pipeline\CheckMateCATConverter;
use Matecat\XliffParser\XliffUtils\Pipeline\CheckSDL;
use Matecat\XliffParser\XliffUtils\Pipeline\CheckXliffVersion2;

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
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     * @throws \Matecat\XliffParser\Exception\NotValidFileException
     */
    public static function getInfo($fullPathToFile)
    {
        self::reset();
        $tmp = self::getFirst1024CharsFromXliff(null, $fullPathToFile);
        self::$fileType['info'] = Files::pathInfo($fullPathToFile);
        self::checkVersion($tmp);

        // run CheckXliffProprietaryPipeline
        $pipeline = new CheckXliffProprietaryPipeline($tmp);
        $pipeline->addCheck(new CheckSDL());
        $pipeline->addCheck(new CheckGlobalSight());
        $pipeline->addCheck(new CheckMateCATConverter());
        $pipeline->addCheck(new CheckXliffVersion2());

        $p = $pipeline->run();

        self::$fileType['proprietary' ] = $p['proprietary' ];
        self::$fileType[ 'proprietary_name' ] = $p['proprietary_name' ];
        self::$fileType[ 'proprietary_short_name' ] = $p['proprietary_short_name' ];
        self::$fileType[ 'converter_version' ] = $p['converter_version' ];

        return self::$fileType;
    }

    /**
     * Reset $fileType
     */
    private static function reset()
    {
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
    private static function getFirst1024CharsFromXliff($stringData = null, $fullPathToFile = null)
    {
        if (!empty($stringData) and empty($fullPathToFile)) {
            $pathInfo = [];
            $stringData = substr($stringData, 0, 1024);
        } elseif (empty($stringData) and !empty($fullPathToFile)) {
            $pathInfo = Files::pathInfo($fullPathToFile);

            if (is_file($fullPathToFile)) {
                $file_pointer = fopen("$fullPathToFile", 'r');
                // Checking Requirements (By specs, I know that xliff version is in the first 1KB)
                $stringData = fread($file_pointer, 1024);
                fclose($file_pointer);
            }
        } elseif (!empty($stringData) && !empty($fullPathToFile)) {
            $pathInfo = Files::pathInfo($fullPathToFile);
        }

        if (!empty($pathInfo) and !Files::isXliff($fullPathToFile)) {
            return false;
        }

        if (!empty($stringData)) {
            return array( $stringData );
        }

        return false;
    }

    /**
     * @param $tmp
     *
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     * @throws \Matecat\XliffParser\Exception\NotValidFileException
     */
    protected static function checkVersion($tmp)
    {
        if (isset($tmp[ 0 ])) {
            self::$fileType[ 'version' ] = XliffVersionDetector::detect($tmp[ 0 ]);
        }
    }

    /**
     * @param string $stringData
     *
     * @return array
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     * @throws \Matecat\XliffParser\Exception\NotValidFileException
     */
    public static function getInfoByStringData($stringData)
    {
        self::reset();

        $tmp = self::getFirst1024CharsFromXliff($stringData);
        self::$fileType['info'] = [];
        self::checkVersion($tmp);

        // run CheckXliffProprietaryPipeline
        $pipeline = new CheckXliffProprietaryPipeline($tmp);
        $pipeline->addCheck(new CheckSDL());
        $pipeline->addCheck(new CheckGlobalSight());
        $pipeline->addCheck(new CheckMateCATConverter());
        $pipeline->addCheck(new CheckXliffVersion2());

        $p = $pipeline->run();

        self::$fileType['proprietary' ] = $p['proprietary' ];
        self::$fileType[ 'proprietary_name' ] = $p['proprietary_name' ];
        self::$fileType[ 'proprietary_short_name' ] = $p['proprietary_short_name' ];
        self::$fileType[ 'converter_version' ] = $p['converter_version' ];

        return self::$fileType;
    }

    /**
     * @param string $fullPath
     * @param null $enforceOnXliff
     * @param null $filterAddress
     *
     * @return bool|int
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     * @throws \Matecat\XliffParser\Exception\NotValidFileException
     */
    public static function fileMustBeConverted($fullPath, $enforceOnXliff = null, $filterAddress = null)
    {
        $convert = true;

        $fileType = self::getInfo( $fullPath );
        $memoryFileType = Files::getMemoryFileType($fullPath);

        if ( Files::isXliff($fullPath) or $memoryFileType ) {

            if ( !empty( $filterAddress ) ) {

                //conversion enforce
                if ( !$enforceOnXliff ) {

                    //if file is not proprietary AND Enforce is disabled
                    //we take it as is
                    if ( !$fileType[ 'proprietary' ] or $memoryFileType ) {
                        $convert = false;
                        //ok don't convert a standard sdlxliff
                    }
                } else {
                    //if conversion enforce is active
                    //we force all xliff files but not files produced by SDL Studio because we can handle them
                    if (
                            $fileType[ 'proprietary_short_name' ] == 'matecat_converter'
                            or $fileType[ 'proprietary_short_name' ] == 'trados'
                            or $fileType[ 'proprietary_short_name' ] == 'xliff_v2'
                            or $memoryFileType
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
            } elseif ( !$fileType[ 'proprietary' ] ) {
                $convert = false;
                //ok don't convert a standard sdlxliff
            }
        }

        return $convert;
    }
}
