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
    protected array $fileType = [];

    /**
     * Handle legacy static calls by delegating to an instance.
     *
     * @param string $name
     * @param array<mixed> $arguments
     *
     * @return mixed
     * @deprecated Use instance methods instead: (new XliffProprietaryDetect())->methodName(...)
     *
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        @trigger_error(
            sprintf(
                'Calling %s::%s() statically is deprecated, use (new %s())->%s() instead.',
                self::class,
                $name,
                self::class,
                $name
            ),
            E_USER_DEPRECATED
        );

        $instance = new self();

        return $instance->$name(...$arguments);
    }

    /**
     * Get file info from the XLIFF content string.
     *
     * @return array<string, mixed>
     */
    public function getInfoFromXliffContent(string $xliffContent): array
    {
        $this->reset();
        $tmp = $this->getFirst1024CharsFromXliff($xliffContent);

        return $this->getInfoFromTmp($tmp);
    }

    /**
     * Get file info from a file path.
     *
     * @return array<string, mixed>
     */
    public function getInfo(string $fullPathToFile): array
    {
        $this->reset();
        $tmp = $this->getFirst1024CharsFromXliff(null, $fullPathToFile);
        $this->fileType['info'] = Files::pathInfo($fullPathToFile);

        return $this->getInfoFromTmp($tmp);
    }

    /**
     * Process tmp content and return file type info.
     *
     * @param array<int, string> $tmp
     *
     * @return array<string, mixed>
     */
    private function getInfoFromTmp(array $tmp): array
    {
        try {
            $this->checkVersion($tmp);
        } catch (Exception) {
            // do nothing - $this->fileType['version'] is left empty
        }

        // run CheckXliffProprietaryPipeline
        $pipeline = $this->runPipeline($tmp);

        $this->fileType['proprietary'] = $pipeline['proprietary'];
        $this->fileType['proprietary_name'] = $pipeline['proprietary_name'];
        $this->fileType['proprietary_short_name'] = $pipeline['proprietary_short_name'];
        $this->fileType['converter_version'] = $pipeline['converter_version'];

        return $this->fileType;
    }

    /**
     * Run the proprietary detection pipeline.
     *
     * @param array<int, string>|null $tmp
     *
     * @return array{proprietary: bool, proprietary_name: string|null, proprietary_short_name: string|null, converter_version: string|null}
     */
    private function runPipeline(?array $tmp = []): array
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
    private function reset(): void
    {
        $this->fileType = [
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
    private function getFirst1024CharsFromString(?string $stringData): string
    {
        if (!empty($stringData)) {
            return substr($stringData, 0, 1024);
        }

        return '';
    }

    /**
     * Get the first 1024 chars from a file.
     */
    private function getFirst1024CharsFromFile(?string $fullPathToFile): string
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
    private function getFirst1024CharsFromXliff(
        ?string $stringData = null,
        ?string $fullPathToFile = null
    ): array {
        $stringData = $this->getFirst1024CharsFromString($stringData);
        if (empty($stringData)) {
            $stringData = $this->getFirst1024CharsFromFile($fullPathToFile);
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
    protected function checkVersion(array $tmp): void
    {
        if (isset($tmp[0])) {
            $this->fileType['version'] = XliffVersionDetector::detect($tmp[0]);
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
    public function getInfoByStringData(string $stringData): array
    {
        $this->reset();

        $tmp = $this->getFirst1024CharsFromXliff($stringData);
        $this->fileType['info'] = [];
        $this->checkVersion($tmp);

        // run CheckXliffProprietaryPipeline
        $pipeline = $this->runPipeline($tmp);

        $this->fileType['proprietary'] = $pipeline['proprietary'];
        $this->fileType['proprietary_name'] = $pipeline['proprietary_name'];
        $this->fileType['proprietary_short_name'] = $pipeline['proprietary_short_name'];
        $this->fileType['converter_version'] = $pipeline['converter_version'];

        return $this->fileType;
    }

    /**
     * Check if the file must be converted.
     *
     * @return bool|int True if it must be converted, false if not, -1 on error
     */
    public function fileMustBeConverted(
        string $fullPath,
        bool $enforceOnXliff = false,
        ?string $filterAddress = null
    ): bool|int {
        $fileType = $this->getInfo($fullPath);
        $memoryFileType = Files::getMemoryFileType($fullPath);

        if (!Files::isXliff($fullPath) && !$memoryFileType) {
            return true;
        }

        if (empty($filterAddress)) {
            return $this->handleNoFilterAddress($fileType);
        }

        return $this->shouldConvertWithFilter($fileType, (bool)$memoryFileType, $enforceOnXliff);
    }

    /**
     * Handle conversion decision when no filter address is provided.
     *
     * @param array<string, mixed> $fileType
     *
     * @return bool|int
     */
    private function handleNoFilterAddress(array $fileType): bool|int
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
    private function shouldConvertWithFilter(
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
