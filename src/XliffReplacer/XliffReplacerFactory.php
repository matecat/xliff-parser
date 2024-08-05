<?php

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\XliffUtils\XliffProprietaryDetect;
use Psr\Log\LoggerInterface;

class XliffReplacerFactory {
    /**
     * @param string                              $originalXliffPath
     * @param array                               $data
     * @param array                               $transUnits
     * @param string                              $targetLang
     * @param string                              $outputFilePath
     * @param bool                                $setSourceInTarget
     * @param LoggerInterface|null                $logger
     * @param XliffReplacerCallbackInterface|null $callback
     *
     * @return Xliff12|Xliff20|XliffSdl
     */
    public static function getInstance(
            string                         $originalXliffPath,
            array                          $data,
            array                          $transUnits,
            string                         $targetLang,
            string                         $outputFilePath,
            bool                           $setSourceInTarget,
            LoggerInterface                $logger = null,
            XliffReplacerCallbackInterface $callback = null
    ) {
        $info = XliffProprietaryDetect::getInfo( $originalXliffPath );

        if ( $info[ 'version' ] == 1 && $info[ 'proprietary_short_name' ] !== 'trados' ) {
            return new Xliff12( $originalXliffPath, $info[ 'version' ], $data, $transUnits, $targetLang, $outputFilePath, $setSourceInTarget, $logger, $callback );
        } elseif ( $info[ 'version' ] == 2 ) {
            return new Xliff20( $originalXliffPath, $info[ 'version' ], $data, $transUnits, $targetLang, $outputFilePath, $setSourceInTarget, $logger, $callback );
        }

        return new XliffSdl( $originalXliffPath, $info[ 'version' ], $data, $transUnits, $targetLang, $outputFilePath, $setSourceInTarget, $logger, $callback );
    }
}
