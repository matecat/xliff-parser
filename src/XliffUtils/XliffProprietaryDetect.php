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
     * Get file info from the XLIFF content string.
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
     * Get file info from a file path.
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
     * Get the first 1024 chars from string data.
     */
    private static function getFirst1024CharsFromString(?string $stringData): string
    {
        if (!empty($stringData)) {
            return substr($stringData, 0, 1024);
        }

        return '';
    }

    /**
     * Get the first 1024 chars from a file.
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
     * Get the first 1024 chars from XLIFF (string or file).
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
     * Check if the file must be converted.
     *
     * @return bool|int True if it must be converted, false if not, -1 on error
     */
    public static function fileMustBeConverted(
        string $fullPath,
        bool $enforceOnXliff = false,
        ?string $filterAddress = null
    ): bool|int {
        $fileType = self::getInfo($fullPath);
        $memoryFileType = Files::getMemoryFileType($fullPath);

        if (!Files::isXliff($fullPath) && !$memoryFileType) {
            return true;
        }

        if (empty($filterAddress)) {
            return self::handleNoFilterAddress($fileType);
        }

        return self::shouldConvertWithFilter($fileType, (bool)$memoryFileType, $enforceOnXliff);
    }

    /**
     * Handle conversion decision when no filter address is provided.
     *
     * @param array<string, mixed> $fileType
     *
     * @return bool|int
     */
    private static function handleNoFilterAddress(array $fileType): bool|int
    {
        if ($fileType['proprietary']) {
            /**
             * Application misconfiguration.
             * upload should not be happened, but if we are here, raise an error.
             * @see upload.class.php
             * */
            return -1;
        }

        return false;
    }

    /**
     * Determine if the XLIFF file needs conversion when the filter service is available.
     *
     * When a filter address is provided, this method decides whether the XLIFF file
     * should be converted based on enforcement settings:
     *
     * - If enforcement is disabled: Skip conversion for non-proprietary files or memory files
     * - If enforcement is enabled: Skip conversion only for files we can handle natively
     *   (MateCat converter, Trados, XLIFF v2) or memory files
     *
     * @param array<string, mixed> $fileType File type information including proprietary status
     * @param bool $memoryFileType Whether a file is a translation memory file
     * @param bool $enforceOnXliff Whether to enforce conversion on XLIFF files
     *
     * @return bool True if a file needs conversion, false otherwise
     */
    private static function shouldConvertWithFilter(
        array $fileType,
        bool $memoryFileType,
        bool $enforceOnXliff
    ): bool {
        if (!$enforceOnXliff) {
            // Without enforcement: convert only proprietary files (unless it's a memory file)
            return $fileType['proprietary'] && !$memoryFileType;
        }

        // With enforcement: skip conversion only for natively handled formats
        $nativelyHandledFormats = ['matecat_converter', 'trados', 'xliff_v2'];
        $isNativelyHandled = in_array($fileType['proprietary_short_name'], $nativelyHandledFormats);

        return !($isNativelyHandled || $memoryFileType);
    }
}
