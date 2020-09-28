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
                $a = str_replace( ' equiv-text="base64:"', '', $match); // complete match. Eg:  <ph id="source1" dataRef="source1"/>, remove every eventual equiv-text="base64:"
                $b = $matches[4][$index];                                            // map identifier. Eg: source1
                $c = $matches[6][$index];                                            // terminator: Eg: >

                // remove every eventual equiv-text="base64:"
                $string = str_replace( ' equiv-text="base64:"', '', $string);

                // if isset a value in the map calculate base64 encoded value
                // otherwise skip
                if (!isset($this->map[$b])) {
                    return $string;
                }

                $value = $this->map[$b];
                $base64EncodedValue = base64_encode($value);

                if(empty($base64EncodedValue) or $base64EncodedValue === ''){
                    return $string;
                }

                // remove eventual equiv-text already present
                $e = str_replace( ' equiv-text="base64:'.$base64EncodedValue.'"', '', $a);

                // replacement
                $d = str_replace('/'.$c, ' equiv-text="base64:'.$base64EncodedValue.'"/'.$c, $e);
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
        // replace eventual empty equiv-text=""
        $string = str_replace(' equiv-text=""', '', $string);

        // regex
        $regex = '/(&lt;|<)(ph|sc|ec)\s?(.*?)\s?dataRef="(.*?)"(.*?)\/(&gt;|>)/si';

        preg_match_all($regex, $string, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $index => $match) {
                $a = $match;              // complete match. Eg:  <ph id="source1" dataRef="source1"/>
                $b = $matches[4][$index]; // map identifier. Eg: source1
                $c = $matches[6][$index]; // terminator: Eg: >

                // if isset a value in the map calculate base64 encoded value
                // or it is an empty string
                // otherwise skip
                if (!isset($this->map[$b]) or empty($this->map[$b]) or $this->map[$b] === '') {
                    return $string;
                }

                $d = str_replace(' equiv-text="base64:'.base64_encode($this->map[$b]).'"/'.$c, '/'.$c, $a);
                $string = str_replace($a, $d, $string);
            }
        }

        return $string;
    }
}
