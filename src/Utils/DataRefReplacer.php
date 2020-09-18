<?php

namespace Matecat\XliffParser\Utils;

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
    public function __construct( array $map ) {
        $this->map = $map;
    }

    /**
     * This function replaces dataRef contained in <ph>, <sc>, <ec> tags against the provided map array
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

        return  preg_replace_callback($regex, function()  {
            return array_shift($this->map);
        }, $string);
    }
}