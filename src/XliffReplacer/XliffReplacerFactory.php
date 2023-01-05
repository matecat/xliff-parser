<?php

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\XliffUtils\XliffProprietaryDetect;
use Psr\Log\LoggerInterface;

class XliffReplacerFactory
{
    /**
     * @param string                              $originalXliffPath
     * @param array                               $data
     * @param array                               $transUnits
     * @param string                              $targetLang
     * @param string                              $outputFilePath
     * @param bool                                $setSourceInTarget
     * @param LoggerInterface                     $logger
     * @param XliffReplacerCallbackInterface|null $callback
     *
     * @return SdlXliffSAXTranslationReplacer|XliffSAXTranslationReplacer
     */
    public static function getInstance($originalXliffPath, &$data, &$transUnits, $targetLang, $outputFilePath, $setSourceInTarget, LoggerInterface $logger = null, XliffReplacerCallbackInterface
    $callback = null)
    {
        $info = XliffProprietaryDetect::getInfo($originalXliffPath);

        if ($info[ 'proprietary_short_name' ] !== 'trados') {
            return new XliffSAXTranslationReplacer($originalXliffPath, $info['version'], $data, $transUnits, $targetLang, $outputFilePath, $setSourceInTarget, $logger, $callback);
        }

        return new SdlXliffSAXTranslationReplacer($originalXliffPath, $info['version'], $data, $transUnits, $targetLang, $outputFilePath, $setSourceInTarget, $logger, $callback);
    }
}
