<?php

namespace Matecat\XliffParser\XliffUtils;

use Exception;
use Matecat\XliffParser\Utils\Files;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckGlobalSight;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckMateCATConverter;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckSDL;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckXliffVersion2;

class XliffProprietaryDetect
{
    /**
     * @var array
     */
    protected static $fileType = [];

    /**
     * @param string $xliffContent
     *
     * @return array
     */
    public static function getInfoFromXliffContent($xliffContent)
    {
        self::reset();
        $tmp = self::getFirst1024CharsFromXliff($xliffContent, null);

        return self::getInfoFromTmp($tmp);
    }

    /**
     * @param $fullPathToFile
     *
     * @return array
     */
    public static function getInfo($fullPathToFile)
    {
        self::reset();
        $tmp = self::getFirst1024CharsFromXliff(null, $fullPathToFile);
        self::$fileType['info'] = Files::pathInfo($fullPathToFile);

        return self::getInfoFromTmp($tmp);
    }

    /**
     * @param $tmp
     *
     * @return array
     */
    private static function getInfoFromTmp( $tmp)
    {
        try {
            self::checkVersion($tmp);
        } catch ( Exception $ignore) {
            // do nothing
            // self::$fileType[ 'version' ] is left empty
        }

        // run CheckXliffProprietaryPipeline
        $pipeline = self::runPipeline($tmp);

        self::$fileType['proprietary' ] = $pipeline['proprietary' ];
        self::$fileType[ 'proprietary_name' ] = $pipeline['proprietary_name' ];
        self::$fileType[ 'proprietary_short_name' ] = $pipeline['proprietary_short_name' ];
        self::$fileType[ 'converter_version' ] = $pipeline['converter_version' ];

        return self::$fileType;
    }

    /**
     * @param $tmp
     *
     * @return array
     */
    private static function runPipeline($tmp)
    {
        $pipeline = new CheckXliffProprietaryPipeline($tmp);
        $pipeline->addCheck(new CheckSDL());
        $pipeline->addCheck(new CheckGlobalSight());
        $pipeline->addCheck(new CheckMateCATConverter());
        $pipeline->addCheck(new CheckXliffVersion2());

        return $pipeline->run();
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
     * @param string $stringData
     * @param string $fullPathToFile
     *
     * @return array|false
     */
    private static function getFirst1024CharsFromXliff($stringData = null, $fullPathToFile = null)
    {
        if (!empty($stringData) && empty($fullPathToFile)) {
            $pathInfo = [];
            $stringData = substr($stringData, 0, 1024);
        } elseif (empty($stringData) && !empty($fullPathToFile)) {
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

        if (!empty($pathInfo) && !Files::isXliff($fullPathToFile)) {
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
        $pipeline = self::runPipeline($tmp);

        self::$fileType['proprietary' ] = $pipeline['proprietary' ];
        self::$fileType[ 'proprietary_name' ] = $pipeline['proprietary_name' ];
        self::$fileType[ 'proprietary_short_name' ] = $pipeline['proprietary_short_name' ];
        self::$fileType[ 'converter_version' ] = $pipeline['converter_version' ];

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

        $fileType = self::getInfo($fullPath);
        $memoryFileType = Files::getMemoryFileType($fullPath);

        if (Files::isXliff($fullPath) || $memoryFileType) {
            if (!empty($filterAddress)) {

                //conversion enforce
                if (!$enforceOnXliff) {

                    //if file is not proprietary AND Enforce is disabled
                    //we take it as is
                    if (!$fileType[ 'proprietary' ] || $memoryFileType) {
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
            } elseif ($fileType[ 'proprietary' ]) {

                /**
                 * Application misconfiguration.
                 * upload should not be happened, but if we are here, raise an error.
                 * @see upload.class.php
                 * */

                $convert = -1;
            //stop execution
            } elseif (!$fileType[ 'proprietary' ]) {
                $convert = false;
                //ok don't convert a standard sdlxliff
            }
        }

        return $convert;
    }
}
