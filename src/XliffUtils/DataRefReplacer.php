<?php

namespace Matecat\XliffParser\XliffUtils;

class DataRefReplacer
{
    /**
     * @var array
     */
    private $map;

    /**
     * DataRefReplacer constructor.
     *
     * @param array $map
     */
    public function __construct( array $map )
    {
        $this->map = $map;
    }

    /**
     * This function inserts a new attribute called 'equiv-text' from dataRef contained in <ph>, <sc>, <ec> tags against the provided map array
     *
     * For a complete reference see:
     *
     * http://docs.oasis-open.org/xliff/xliff-core/v2.1/os/xliff-core-v2.1-os.html#dataref
     *
     * @param string $string
     *
     * @return string
     */
    public function replace($string)
    {
        $regex = "/<(ph|sc|ec)\s?(.*?)\s?dataRef=\"(.*?)\"(.*?)\/>/si";

        return  preg_replace_callback($regex, function($match) use ($string) {
            if(empty($match)){
                return $string;
            }

            return str_replace('/>', ' equiv-text="'.$this->map[$match[3]].'"/>', $match[0]);
        }, $string);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function restore( $string )
    {
        $regex = "/<(ph|sc|ec)\s?(.*?)\s?equiv-text=\"(.*?)\"(.*?)\/>/si";

        return  preg_replace_callback($regex, function($match) use ($string) {
            if(empty($match)){
                return $string;
            }

            return str_replace(' equiv-text="'.$match[3].'"/>', '/>', $match[0]);
        }, $string);
    }
}