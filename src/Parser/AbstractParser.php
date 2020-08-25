<?php

namespace Matecat\XliffParser\Parser;

use Matecat\XliffParser\Utils\Strings;

abstract class AbstractParser
{
    /**
     * @param \DOMDocument $dom
     *
     * @return array
     */
    abstract public function parse(\DOMDocument $dom, $output = []);

    /**
     * Extract attributes if they are present
     *
     * Ex:
     * <p align=center style="font-size: 12px;">some text</p>
     *
     * $attr->nodeName == 'align' :: $attr->nodeValue == 'center'
     * $attr->nodeName == 'style' :: $attr->nodeValue == 'font-size: 12px;'
     *
     * @param \DOMElement $element
     *
     * @return array
     */
    protected function extractTagAttributes(\DOMElement $element)
    {
        $tagAttributes = [];

        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attr) {
                $tagAttributes[ $attr->nodeName ] = $attr->nodeValue;
            }
        }

        return $tagAttributes;
    }

    /**
     * @param \DOMDocument $dom
     * @param \DOMElement  $element
     *
     * @return string
     */
    protected function extractTagContent(\DOMDocument $dom, \DOMElement $element)
    {
        $childNodes = $element->hasChildNodes();
        $extractedContent = '';

        if (!empty($childNodes)) {
            foreach ($element->childNodes as $node) {
                $extractedContent .= $dom->saveXML($node);
            }
        }

        return trim($extractedContent);
    }

    /**
     * @param $noteValue
     *
     * @return array
     * @throws \Exception
     */
    protected function JSONOrRawContentArray($noteValue)
    {
        if (Strings::isJSON($noteValue)) {
            return ['json' => Strings::cleanCDATA($noteValue)];
        }

        return ['raw-content' => Strings::fixNonWellFormedXml($noteValue)];
    }
}
