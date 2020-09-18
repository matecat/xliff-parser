<?php

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

class CheckSDL implements CheckInterface
{
    public function check($tmp)
    {
        $fileType = [];

        if (isset($tmp[ 0 ])) {
            if (stripos($tmp[ 0 ], 'sdl:version') !== false) {
                //little trick, we consider not proprietary Sdlxliff files because we can handle them
                $fileType[ 'proprietary' ]            = false;
                $fileType[ 'proprietary_name' ]       = 'SDL Studio ';
                $fileType[ 'proprietary_short_name' ] = 'trados';
                $fileType[ 'converter_version' ]      = 'legacy';

                return $fileType;
            }
        }
    }
}
