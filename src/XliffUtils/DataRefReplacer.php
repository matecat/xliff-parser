<?php

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\Utils\Strings;

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
        // if map is empty return string as is
        if(empty($this->map)){
            return  $string;
        }

        // 1. Replace for ph|sc|ec tag
        $regex = '/(&lt;|<)(ph|sc|ec)\s?(.*?)\s?dataRef="(.*?)"(.*?)\/(&gt;|>)/si';

        // clean string from equiv-text eventually present
        $string = $this->cleanFromEquivText($string);

        preg_match_all($regex, $string, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $index => $match) {
                $a = $match;                // complete match. Eg:  <ph id="source1" dataRef="source1"/>
                $b = $matches[4][$index];   // map identifier. Eg: source1
                $c = $matches[6][$index];   // terminator: Eg: >

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

                // replacement
                $d = str_replace('/'.$c, ' equiv-text="base64:'.$base64EncodedValue.'"/'.$c, $a);
                $string = str_replace($a, $d, $string);
            }
        }

        // 2. Replace for tag
        $regex = '/(&lt;|<)pc\s?(.*?)(&gt;|>)(.*?)(&lt;|<)\/pc(&gt;|>)/';

        preg_match_all($regex, $string, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $index => $match) {
                $a = $match;               // <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>
                $b = $matches[2][$index];  // id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1"
                $c = $matches[4][$index];  // La Repubblica

                preg_match('/\s?dataRefEnd="(.*?)"\s?/', $b, $dataRefEndMatches);
                preg_match('/\s?dataRefStart="(.*?)"\s?/', $b, $dataRefStartMatches);
                preg_match('/\s?id="(.*?)"\s?/', $b, $idMatches);

                if(!empty($dataRefEndMatches[1]) and !empty($dataRefStartMatches[1])){

                    $startValue = $this->map[$dataRefStartMatches[1]];
                    $base64EncodedStartValue = base64_encode($startValue);

                    $endValue = $this->map[$dataRefEndMatches[1]];
                    $base64EncodedEndValue = base64_encode($endValue);

                    $startOriginalData = '<pc '.$b.'>';
                    $endOriginalData = '</pc>';
                    $base64StartOriginalData = base64_encode($startOriginalData);
                    $base64EndOriginalData = base64_encode($endOriginalData);


                    $d  = '<ph '. ((isset($idMatches[1])) ? 'id="'.$idMatches[1].'_1"' : '') .' dataType="pcStart" originalData="'.$base64StartOriginalData.'" dataRef="'.$dataRefStartMatches[1].'" equiv-text="base64:'
                            .$base64EncodedStartValue.'"/>';
                    $d .= $c;
                    $d .= '<ph '. ((isset($idMatches[1])) ? 'id="'.$idMatches[1].'_2"': '') .' dataType="pcEnd" originalData="'.$base64EndOriginalData.'" dataRef="'.$dataRefEndMatches[1].'" equiv-text="base64:' .$base64EncodedEndValue.'"/>';
                    $string = str_replace($a, $d, $string);
                }
            }
        }

        return $string;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function cleanFromEquivText($string)
    {
        return preg_replace('/ equiv-text="(.*?)"/', '', $string);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function restore($string)
    {
        // if map is empty return string as is
        if(empty($this->map)){
            return  $string;
        }

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

                // if <ph> tag has originalData and originalType is pcStart or pcEnd, replace with original data
                if(Strings::contains('dataType="pcStart"', $d) or Strings::contains('dataType="pcEnd"', $d)){
                    preg_match('/\s?originalData="(.*?)"\s?/', $d, $originalDataMatches);

                    if(isset($originalDataMatches[1])){
                        $originalData = base64_decode($originalDataMatches[1]);
                        $string = str_replace($d, $originalData, $string);
                    }
                }
            }
        }

        return $string;
    }
}
