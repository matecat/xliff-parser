<?php

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\Utils\FlatData;
use Matecat\XliffParser\Utils\HtmlParser;
use Matecat\XliffParser\Utils\Strings;

class DataRefReplacer
{
    /**
     * @var array
     */
    private $map;

    /**
     * @var bool
     */
    private $escapedHtml;

    /**
     * DataRefReplacer constructor.
     *
     * @param array $map
     * @param bool  $escapedHtml
     */
    public function __construct( array $map, $escapedHtml = false)
    {
        $this->map         = $map;
        $this->escapedHtml = $escapedHtml;
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
        if (empty($this->map)) {
            return  $string;
        }

        // clean string from equiv-text eventually present
        $string = $this->cleanFromEquivText($string);

        $html = HtmlParser::parse($string, $this->escapedHtml);

        foreach ($html as $node) {
            // 1. Replace for ph|sc|ec tag
            if ($node->tagname === 'ph' or $node->tagname === 'sc' or $node->tagname === 'ec') {
                $string = $this->addEquivText($node, $string);
            }

            // 2. Replace tag <pc>
            if ($node->tagname === 'pc') {
                $string = $this->convertPcToPhAndAddEquivText($node, $string);
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
     * @param object $node
     * @param string $string
     *
     * @return string
     */
    private function addEquivText($node, $string)
    {
        if (!isset($node->attributes['dataRef'])) {
            return $string;
        }

        $a = $node->node;  // complete match. Eg:  <ph id="source1" dataRef="source1"/>
        $b = $node->attributes['dataRef'];   // map identifier. Eg: source1

        // if isset a value in the map calculate base64 encoded value
        // otherwise skip
        if (!isset($this->map[$b])) {
            return $string;
        }

        $value = $this->map[$b];
        $base64EncodedValue = base64_encode($value);

        if (empty($base64EncodedValue) or $base64EncodedValue === '') {
            return $string;
        }

        // replacement
        $d = str_replace('/', ' equiv-text="base64:'.$base64EncodedValue.'"/', $a);

        return str_replace($a, $d, $string);
    }

    /**
     * @param object $node
     * @param string $string
     *
     * @return string
     */
    private function convertPcToPhAndAddEquivText($node, $string)
    {
        $a = $node->node; // <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>

        if (isset($node->attributes['dataRefEnd']) and isset($node->attributes['dataRefStart'])) {
            $startValue = $this->map[$node->attributes['dataRefStart']];
            $base64EncodedStartValue = base64_encode($startValue);

            $endValue = $this->map[$node->attributes['dataRefEnd']];
            $base64EncodedEndValue = base64_encode($endValue);

            $startOriginalData = '<pc '. FlatData::flatArray($node->attributes, ' ', '=')  .'>';
            $endOriginalData = '</pc>';

            if ($this->escapedHtml) {
                $startOriginalData = Strings::htmlentities($startOriginalData);
                $endOriginalData = Strings::htmlentities($endOriginalData);
            }

            $base64StartOriginalData = base64_encode($startOriginalData);
            $base64EndOriginalData = base64_encode($endOriginalData);

            $d  = '<ph '. ((isset($node->attributes['id'])) ? 'id="'.$node->attributes['id'].'_1"' : '') .' dataType="pcStart" originalData="'.$base64StartOriginalData.'" dataRef="'
                    .$node->attributes['dataRefStart'].'" equiv-text="base64:'
                    .$base64EncodedStartValue.'"/>';

            if (!$node->has_children) {
                $d .= $node->inner_html;
            } else {
                foreach ($node->inner_html as $nestedNode) {
                    $d .= $this->convertPcToPhAndAddEquivText($nestedNode, $nestedNode->node);
                }
            }

            $d .= '<ph '. ((isset($node->attributes['id'])) ? 'id="'.$node->attributes['id'].'_2"': '') .' dataType="pcEnd" originalData="'.$base64EndOriginalData.'" dataRef="'.$node->attributes['dataRefEnd'].'" equiv-text="base64:' .$base64EncodedEndValue.'"/>';

            $string = str_replace($a, $d, $string);
        }

        if ($this->escapedHtml) {
            $string = Strings::htmlentities($string);
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
        // if map is empty return string as is
        if (empty($this->map)) {
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
                if (Strings::contains('dataType="pcStart"', $d) or Strings::contains('dataType="pcEnd"', $d)) {
                    preg_match('/\s?originalData="(.*?)"\s?/', $d, $originalDataMatches);

                    if (isset($originalDataMatches[1])) {
                        $originalData = base64_decode($originalDataMatches[1]);
                        $string = str_replace($d, $originalData, $string);
                    }
                }
            }
        }

        return $string;
    }
}
