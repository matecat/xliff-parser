<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffUtils;

use Exception;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\Utils\Files;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckGlobalSight;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckMateCATConverter;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckSDL;
use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckXliffVersion2;

class XliffProprietaryDetect
{

    /** @var array<string, mixed> */
    protected static array $fileType = [];

    /**
     * Get file info from XLIFF content string.
     *
     * @return array<string, mixed>
     */
    public static function getInfoFromXliffContent(string $xliffContent): array
    {
        self::reset();
        $tmp = self::getFirst1024CharsFromXliff($xliffContent);

        return self::getInfoFromTmp($tmp);
    }

    /**
     * Get file info from file path.
     *
     * @return array<string, mixed>
     */
    public static function getInfo(string $fullPathToFile): array
    {
        self::reset();
        $tmp = self::getFirst1024CharsFromXliff(null, $fullPathToFile);
        self::$fileType['info'] = Files::pathInfo($fullPathToFile);

        return self::getInfoFromTmp($tmp);
    }

    /**
     * Process tmp content and return file type info.
     *
     * @param array<int, string> $tmp
     *
     * @return array<string, mixed>
     */
    private static function getInfoFromTmp(array $tmp): array
    {
        try {
            self::checkVersion($tmp);
        } catch (Exception) {
            // do nothing - self::$fileType['version'] is left empty
        }

        // run CheckXliffProprietaryPipeline
        $pipeline = self::runPipeline($tmp);

        self::$fileType['proprietary'] = $pipeline['proprietary'];
        self::$fileType['proprietary_name'] = $pipeline['proprietary_name'];
        self::$fileType['proprietary_short_name'] = $pipeline['proprietary_short_name'];
        self::$fileType['converter_version'] = $pipeline['converter_version'];

        return self::$fileType;
    }

    /**
     * Run the proprietary detection pipeline.
     *
     * @param array<int, string>|null $tmp
     *
     * @return array{proprietary: bool, proprietary_name: string|null, proprietary_short_name: string|null, converter_version: string|null}
     */
    private static function runPipeline(?array $tmp = []): array
    {
        $pipeline = new CheckXliffProprietaryPipeline($tmp);
        $pipeline->addCheck(new CheckSDL());
        $pipeline->addCheck(new CheckGlobalSight());
        $pipeline->addCheck(new CheckMateCATConverter());
        $pipeline->addCheck(new CheckXliffVersion2());

        return $pipeline->run();
    }

    /**
     * Reset fileType to default values.
     */
    private static function reset(): void
    {
        self::$fileType = [
            'info' => [],
            'version' => null,
            'proprietary' => false,
            'proprietary_name' => null,
            'proprietary_short_name' => null,
        ];
    }

    /**
     * Get first 1024 chars from string data.
     */
    private static function getFirst1024CharsFromString(?string $stringData): string
    {
        if (!empty($stringData)) {
            return substr($stringData, 0, 1024);
        }

        return '';
    }

    /**
     * Get first 1024 chars from file.
     */
    private static function getFirst1024CharsFromFile(?string $fullPathToFile): string
    {
        if (empty($fullPathToFile) || !is_file($fullPathToFile)) {
            return '';
        }

        $filePointer = @fopen($fullPathToFile, 'r');
        if ($filePointer === false) {
            return '';
        }

        // By specs, XLIFF version is in the first 1KB
        $stringData = fread($filePointer, 1024);
        fclose($filePointer);

        return $stringData !== false ? $stringData : '';
    }

    /**
     * Get first 1024 chars from XLIFF (string or file).
     *
     * @return array<int, string>
     */
    private static function getFirst1024CharsFromXliff(
        ?string $stringData = null,
        ?string $fullPathToFile = null
    ): array {
        $stringData = self::getFirst1024CharsFromString($stringData);
        if (empty($stringData)) {
            $stringData = self::getFirst1024CharsFromFile($fullPathToFile);
        }

        return !empty($stringData) ? [$stringData] : [];
    }

    /**
     * Check and set XLIFF version.
     *
     * @param array<int, string> $tmp
     *
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    protected static function checkVersion(array $tmp): void
    {
        if (isset($tmp[0])) {
            self::$fileType['version'] = XliffVersionDetector::detect($tmp[0]);
        }
    }

    /**
     * Get file info by string data.
     *
     * @return array<string, mixed>
     *
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    public static function getInfoByStringData(string $stringData): array
    {
        self::reset();

        $tmp = self::getFirst1024CharsFromXliff($stringData);
        self::$fileType['info'] = [];
        self::checkVersion($tmp);

        // run CheckXliffProprietaryPipeline
        $pipeline = self::runPipeline($tmp);

        self::$fileType['proprietary'] = $pipeline['proprietary'];
        self::$fileType['proprietary_name'] = $pipeline['proprietary_name'];
        self::$fileType['proprietary_short_name'] = $pipeline['proprietary_short_name'];
        self::$fileType['converter_version'] = $pipeline['converter_version'];

        return self::$fileType;
    }

    /**
     * Check if file must be converted.
     *
     * @return bool|int True if must convert, false if not, -1 on error
     */
    public static function fileMustBeConverted(
        string $fullPath,
        bool $enforceOnXliff = false,
        ?string $filterAddress = null
    ): bool|int {
        $convert = true;

        $fileType = self::getInfo($fullPath);
        $memoryFileType = Files::getMemoryFileType($fullPath);

        if (Files::isXliff($fullPath) || $memoryFileType) {
            if (!empty($filterAddress)) {
                //conversion enforce
                if (!$enforceOnXliff) {
                    //if file is not proprietary AND Enforce is disabled
                    //we take it as is
                    if (!$fileType['proprietary'] || $memoryFileType) {
                        $convert = false;
                        //ok don't convert a standard sdlxliff
                    }
                } else {
                    //if conversion enforce is active
                    //we force all xliff files but not files produced by SDL Studio because we can handle them
                    if (in_array($fileType['proprietary_short_name'], ['matecat_converter', 'trados', 'xliff_v2']
                        ) || $memoryFileType) {
                        $convert = false;
                        //ok don't convert a standard sdlxliff
                    }
                }
            } elseif ($fileType['proprietary']) {
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
