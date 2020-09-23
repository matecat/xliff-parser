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
    public function __construct(array $map)
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
        $regex = '/(&lt;|<)(ph|sc|ec)\s?(.*?)\s?dataRef="(.*?)"(.*?)\/(&gt;|>)/si';

        preg_match_all($regex, $string, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $index => $match) {
                $a = $match;              // complete match. Eg:  <ph id="source1" dataRef="source1"/>
                $b = $matches[4][$index]; // id. Eg: source1
                $c = $matches[6][$index]; // terminator: Eg: >
                $d = str_replace('/'.$c, ' equiv-text="'.$this->map[$b].'"/'.$c, $a);
                $string = str_replace($a, $d, $string);
            }
        }

        return $string;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function restore($string)
    {
        $regex = '/(&lt;|<)(ph|sc|ec)\s?(.*?)\s?dataRef="(.*?)"(.*?)\/(&gt;|>)/si';

        preg_match_all($regex, $string, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $index => $match) {
                $a = $match;              // complete match. Eg:  <ph id="source1" dataRef="source1"/>
                $b = $matches[4][$index]; // id. Eg: source1
                $c = $matches[6][$index]; // terminator: Eg: >
                $d = str_replace(' equiv-text="'.$this->map[$b].'"/'.$c, '/'.$c, $a);
                $string = str_replace($a, $d, $string);
            }
        }

        return $string;
    }
}
