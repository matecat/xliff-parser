<?php

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\XliffUtils\XliffProprietaryDetect;

class XliffReplacerFactory
{
    /**
     * @param $originalXliffPath
     * @param $data
     * @param $transUnits
     * @param $targetLang
     * @param $outputFilePath
     * @param XliffReplacerCallbackInterface|null $callback
     *
     * @return SdlXliffSAXTranslationReplacer|XliffSAXTranslationReplacer
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     * @throws \Matecat\XliffParser\Exception\NotValidFileException
     */
    public static function getInstance($originalXliffPath, &$data, &$transUnits, $targetLang, $outputFilePath, XliffReplacerCallbackInterface $callback = null)
    {
        $info = XliffProprietaryDetect::getInfo($originalXliffPath);

        if ($info[ 'proprietary_short_name' ] !== 'trados') {
            return new XliffSAXTranslationReplacer($originalXliffPath, $info['version'], $data, $transUnits, $targetLang, $outputFilePath, $callback);
        }

        return new SdlXliffSAXTranslationReplacer($originalXliffPath, $info['version'], $data, $transUnits, $targetLang, $outputFilePath, $callback);
    }
}
