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
     * DataRefReplacer constructor.
     *
     * @param array $map
     */
    public function __construct( array $map)
    {
        $this->map = $map;
    }

    /**
     * This function inserts a new attribute called 'equiv-text' from dataRef contained in <ph>, <sc>, <ec>, <pc> tags against the provided map array
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
        // if map is empty
        // or the string has not a dataRef attribute
        // return string as is
        if (empty($this->map) or !$this->hasAnyDataRefAttribute($string)) {
            return $string;
        }

        // (recursively) clean string from equiv-text eventually present
        $string = $this->cleanFromEquivText($string);

        $html = HtmlParser::parse($string);

        // create a dataRefEnd map
        // (needed for correct handling of </pc> closing tags)
        $dataRefEndMap = $this->buildDataRefEndMap($html);

        // parse and process each node of the original string
        foreach ($html as $node){
            // 1. Replace for ph|sc|ec tag
            if ($node->tagname === 'ph' or $node->tagname === 'sc' or $node->tagname === 'ec') {
                $string = $this->addEquivTextToPhTag($node, $string);
            }

            // 2. Replace tag <pc>
            if ($node->tagname === 'pc') {
                $string = $this->convertPcToPhAndAddEquivText($node, $string, $dataRefEndMap);
            }
        }

        return $string;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function hasAnyDataRefAttribute($string)
    {
        $dataRefTags = [
            'dataRef',
            'dataRefStart',
            'dataRefEnd',
        ];

        foreach ($dataRefTags as $tag){
            preg_match('/ '.$tag.'=[\\\\"](.*?)[\\\\"]/', $string, $matches);

            if(count($matches) > 0){
                return true;
            }
        }
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function cleanFromEquivText($string)
    {
        $html = HtmlParser::parse($string);

        foreach ($html as $node){
            $string = $this->recursiveCleanFromEquivText($node, $string);
        }

        return $string;
    }

    /**
     * Build the DataRefEndMap needed by replaceClosingPcTags function
     * (only for <pc> tags handling)
     *
     * @param $html
     *
     * @return array
     */
    private function buildDataRefEndMap($html)
    {
        $dataRefEndMap = [];

        foreach ($html as $index => $node) {
            if ( $node->tagname === 'pc' ) {
                $this->extractDataRefMapRecursively($node, $dataRefEndMap);
            }
        }

        return $dataRefEndMap;
    }

    /**
     * Extract (recursively) the dataRefEnd map from single nodes
     *
     * @param object $node
     * @param $dataRefEndMap
     */
    private function extractDataRefMapRecursively( $node, &$dataRefEndMap)
    {
        if($this->nodeContainsNestedPcTags($node)) {
            foreach ( $node->inner_html as $nestedNode ) {
                $this->extractDataRefMapRecursively($nestedNode, $dataRefEndMap);
            }
        }

        $dataRefEndMap[] = [
                'id' => isset($node->attributes['id'] ) ? $node->attributes['id'] : null,
                'dataRefEnd' => isset($node->attributes['dataRefEnd']) ? $node->attributes['dataRefEnd'] : $node->attributes['dataRefStart'],
        ];
    }

    /**
     * @param object $node
     * @param $string
     *
     * @return string|string[]
     */
    private function recursiveCleanFromEquivText($node, $string)
    {
        if($node->has_children){
            foreach ($node->inner_html as $childNode){
                $string = $this->recursiveCleanFromEquivText($childNode, $string);
            }
        } else {
            if(isset($node->attributes['id']) and array_key_exists($node->attributes['id'], $this->map)){
                $cleaned = preg_replace('/ equiv-text="(.*?)"/', '', $node->node);
                $string = str_replace($node->node, $cleaned, $string);
            }
        }

        return $string;
    }

    /**
     * @param object $node
     * @param string $string
     *
     * @return string
     */
    private function addEquivTextToPhTag( $node, $string)
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

        $a = str_replace(['<','>','&gt;', '&lt;'], '', $a);
        $d = str_replace(['<','>','&gt;', '&lt;'], '', $d);

        return str_replace($a, $d, $string);
    }

    /**
     * This function converts recursively <pc> tags to <ph> tags for Matecat
     *
     * @param object $node
     * @param string $string
     * @param array  $dataRefEndMap
     *
     * @return string
     */
    private function convertPcToPhAndAddEquivText($node, $string, $dataRefEndMap = [])
    {
        // Proceed with conversion to <ph> only if there is at least `dataRefEnd` OR `dataRefEnd` attribute
        if (isset($node->attributes['dataRefEnd']) or isset($node->attributes['dataRefStart'])) {
            $toBeEscaped = Strings::isAnEscapedHTML($node->node);
            $string = self::replaceOpeningPcTags($string, $toBeEscaped);
            $string = self::replaceClosingPcTags($string, $toBeEscaped, $dataRefEndMap);
            $string = ($toBeEscaped) ? Strings::escapeOnlyHTMLTags($string) : $string;
        }

        return $string;
    }

    /**
     * Replace opening <pc> tags with correct reference in the $string
     *
     * @param string $string
     * @param bool $toBeEscaped
     *
     * @return string
     */
    private function replaceOpeningPcTags($string, $toBeEscaped)
    {
        $regex = ($toBeEscaped) ? '/&lt;pc(.*?)&gt;/iu' : '/<pc(.*?)>/iu';
        preg_match_all($regex, $string, $openingPcMatches);

        if(isset($openingPcMatches[0]) and count($openingPcMatches[0])>0){
            foreach ($openingPcMatches[0] as $index => $match){
                $attr = HtmlParser::getAttributes($openingPcMatches[1][$index]);

                // CASE 1 - Missing `dataRefStart`
                if( isset($attr['dataRefEnd']) and !isset($attr['dataRefStart'])  ){
                    $attr['dataRefStart'] = $attr['dataRefEnd'];
                }

                // CASE 2 - Missing `dataRefEnd`
                if( isset($attr['dataRefStart']) and !isset($attr['dataRefEnd'])  ){
                    $attr['dataRefEnd'] = $attr['dataRefStart'];
                }

                if(isset($attr['dataRefStart'])){
                    $startOriginalData = $match; // opening <pc>
                    $startValue = $this->map[$attr['dataRefStart']];
                    $base64EncodedStartValue = base64_encode($startValue);
                    $base64StartOriginalData = base64_encode($startOriginalData);

                    // conversion for opening <pc> tag
                    $openingPcConverted  = '<ph '. ((isset($attr['id'])) ? 'id="'.$attr['id'].'_1"' : '') .' dataType="pcStart" originalData="'.$base64StartOriginalData.'" dataRef="'
                            .$attr['dataRefStart'].'" equiv-text="base64:'
                            .$base64EncodedStartValue.'"/>';

                    $string = str_replace($startOriginalData, $openingPcConverted, $string);
                }
            }
        }

        return $string;
    }

    /**
     * Replace closing </pc> tags with correct reference in the $string
     * thanks to $dataRefEndMap
     *
     * @param string $string
     * @param bool   $toBeEscaped
     * @param array  $dataRefEndMap
     *
     * @return string
     */
    private function replaceClosingPcTags($string, $toBeEscaped, $dataRefEndMap = [])
    {
        $regex = ($toBeEscaped) ? '/&lt;\/pc&gt;/iu' : '/<\/pc>/iu';
        preg_match_all($regex, $string, $closingPcMatches, PREG_OFFSET_CAPTURE);
        $delta = 0;

        if(isset($closingPcMatches[0]) and count($closingPcMatches[0])>0) {
            foreach ( $closingPcMatches[ 0 ] as $index => $match ) {
                $offset = $match[1];
                $length = strlen($match[0]);
                $attr = $dataRefEndMap[$index];

                if(!empty($attr) and isset($attr['dataRefEnd'])){
                    $endOriginalData = $match[0]; // </pc>
                    $endValue = $this->map[$attr['dataRefEnd']];
                    $base64EncodedEndValue = base64_encode($endValue);
                    $base64EndOriginalData = base64_encode($endOriginalData);

                    // conversion for closing <pc> tag
                    $closingPcConverted = '<ph '. ((isset($attr['id'])) ? 'id="'.$attr['id'].'_2"': '') .' dataType="pcEnd" originalData="'.$base64EndOriginalData.'" dataRef="'
                            .$attr['dataRefEnd'].'" equiv-text="base64:' .$base64EncodedEndValue.'"/>';

                    $realOffset = ($delta === 0) ? $offset : ($offset + $delta);

                    $string = substr_replace($string, $closingPcConverted, $realOffset, $length);
                    $delta = $delta + strlen($closingPcConverted) - $length;
                }
            }
        }

        return $string;
    }

    /**
     * @param object $node
     *
     * @return bool
     */
    private function nodeContainsNestedPcTags($node)
    {
        if(!$node->has_children){
            return false;
        }

        foreach ($node->inner_html as $nestedNode) {
            if($nestedNode->tagname === 'pc' and (isset($node->attributes['dataRefEnd']) or isset($node->attributes['dataRefStart']))){
                return true;
            }
        }

        return false;
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
        $html = HtmlParser::parse($string);

        foreach ($html as $node){
            $string = $this->recursiveRemoveOriginalData($node, $string);
        }

        return $string;
    }

    /**
     * @param object $node
     * @param $string
     *
     * @return string|string[]
     */
    private function recursiveRemoveOriginalData($node, $string)
    {
        if($node->has_children){
            foreach ($node->inner_html as $childNode){
                $string = $this->recursiveRemoveOriginalData($childNode, $string);
            }
        } else {

            if(!isset($node->attributes['dataRef'])){
                return $string;
            }

            $a = $node->node;                  // complete match. Eg:  <ph id="source1" dataRef="source1"/>
            $b = $node->attributes['dataRef']; // map identifier. Eg: source1
            $c = $node->terminator;            // terminator: Eg: >

            // if isset a value in the map calculate base64 encoded value
            // or it is an empty string
            // otherwise skip
            if (!isset($this->map[$b]) or empty($this->map[$b]) or $this->map[$b] === '') {
                return $string;
            }

            $d = str_replace(' equiv-text="base64:'.base64_encode($this->map[$b]).'"/'.$c, '/'.$c, $a);

            // replace only content tag, no matter if the string is encoded or not
            // in this way we can handle string with mixed tags (encoded and not-encoded)
            // in the same string
            $a = self::purgeTags($a);
            $d = self::purgeTags($d);

            $string = str_replace($a, $d, $string);

            // if <ph> tag has originalData and originalType is pcStart or pcEnd, replace with original data
            if (Strings::contains('dataType="pcStart"', $d) or Strings::contains('dataType="pcEnd"', $d)) {
                preg_match('/\s?originalData="(.*?)"\s?/', $d, $originalDataMatches);

                if (isset($originalDataMatches[1])) {
                    $originalData = base64_decode($originalDataMatches[1]);
                    $originalData = $this->purgeTags($originalData);
                    $string = str_replace($d, $originalData, $string);
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
    private function purgeTags($string)
    {
        return str_replace(['<', '>', '&lt;', '&gt;'], '', $string);
    }
}
