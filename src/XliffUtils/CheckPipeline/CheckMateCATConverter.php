<?php

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

class CheckMateCATConverter implements CheckInterface
{
    /**
     * @param string $tmp
     *
     * @return array|null
     */
    public function check($tmp)
    {
        $fileType = [];

        if (isset($tmp[ 0 ])) {
            preg_match('#tool-id\s*=\s*"matecat-converter(\s+([^"]+))?"#i', $tmp[ 0 ], $matches);
            if (!empty($matches)) {
                $fileType[ 'proprietary' ]            = false;
                $fileType[ 'proprietary_name' ]       = 'MateCAT Converter';
                $fileType[ 'proprietary_short_name' ] = 'matecat_converter';
                if (isset($matches[ 2 ])) {
                    $fileType[ 'converter_version' ] = $matches[ 2 ];
                } else {
                    // First converter release didn't specify version
                    $fileType[ 'converter_version' ] = '1.0';
                }

                return $fileType;
            }
        }
    }
}
