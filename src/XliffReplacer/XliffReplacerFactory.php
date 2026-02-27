<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\XliffUtils\XliffProprietaryDetect;
use Psr\Log\LoggerInterface;

final class XliffReplacerFactory
{

    /**
     * Create the appropriate XliffReplacer instance based on the file type.
     *
     * @param string $originalXliffPath Path to original XLIFF file
     * @param array<int|string, array<string, mixed>> $data Translation data
     * @param array<int|string, array<int, int>> $transUnits Trans-unit mapping
     * @param string $targetLang Target language code
     * @param string $outputFilePath Path for an output file
     * @param bool $setSourceInTarget Whether to copy a source to a target
     * @param LoggerInterface|null $logger Optional logger
     * @param XliffReplacerCallbackInterface|null $callback Optional callback
     *
     * @return AbstractXliffReplacer
     */
    public static function getInstance(
        string $originalXliffPath,
        array $data,
        array $transUnits,
        string $targetLang,
        string $outputFilePath,
        bool $setSourceInTarget,
        ?LoggerInterface $logger = null,
        ?XliffReplacerCallbackInterface $callback = null
    ): AbstractXliffReplacer {
        $info = (new XliffProprietaryDetect())->getInfo($originalXliffPath);
        $version = (int)($info['version'] ?? 0);

        if ($version === 1 && $info['proprietary_short_name'] !== 'trados') {
            return new Xliff12(
                $originalXliffPath,
                $version,
                $data,
                $transUnits,
                $targetLang,
                $outputFilePath,
                $setSourceInTarget,
                $logger,
                $callback
            );
        }

        if ($version === 2) {
            return new Xliff20(
                $originalXliffPath,
                $version,
                $data,
                $transUnits,
                $targetLang,
                $outputFilePath,
                $setSourceInTarget,
                $logger,
                $callback
            );
        }

        return new XliffSdl(
            $originalXliffPath,
            $version,
            $data,
            $transUnits,
            $targetLang,
            $outputFilePath,
            $setSourceInTarget,
            $logger,
            $callback
        );
    }
}
