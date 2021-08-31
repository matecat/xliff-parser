<?php

namespace Matecat\XliffParser\Utils;

class HtmlParser
{
    const ORIGINAL_TEXT_PLACEHOLDER = '#####__ORIGINAL_TEXT__#####';
    const LT_PLACEHOLDER = '#####__LT_PLACEHOLDER__#####';

    /**
     * This solution is taken from here and then modified:
     * https://www.php.net/manual/fr/regexp.reference.recursive.php#95568
     *
     * @param string $html
     *
     * @return array
     */
    public static function parse($html)
    {
        $toBeEscaped = Strings::isAnEscapedHTML($html);

        if ($toBeEscaped) {
            $html = Strings::htmlspecialchars_decode($html);
        }

        $html = self::protectNotHtmlLessThanSymbols($html);

        return self::extractHtmlNode($html, $toBeEscaped);
    }

    /**
     * Protect all < symbols that are not part of html tags.
     *
     * Example:
     *
     * <div id="1">< Ciao <<div id="2"></div></div>
     *
     * is converted to:
     *
     * <div id="1">#####__LT_PLACEHOLDER__##### Ciao #####__LT_PLACEHOLDER__#####<div id="2"></div></div>
     *
     * @param string $html
     *
     * @return string
     */
    private static function protectNotHtmlLessThanSymbols($html)
    {
        preg_match_all('/<|>/iu', $html, $matches, PREG_OFFSET_CAPTURE);

        $delta = 0;

        foreach ($matches[0] as $key => $match) {

            $current = $matches[ 0 ][ $key ][ 0 ];

            if(isset($matches[0][$key+1][0])){
                $next = $matches[0][$key+1][0];
                $nextOffset = $matches[0][$key+1][1];
                $realNextOffset = ($delta === 0) ? $nextOffset : ($nextOffset + $delta);
            }

            $length = strlen($match[0]);
            $offset = $matches[0][$key][1];
            $realOffset = ($delta === 0) ? $offset : ($offset + $delta);

            if( $current === '<' and isset($next)){

                // 1. if next is > or
                // 2. next is < and is not html tag (like < > for example)
                $insideAngularTags = substr($html, $realOffset, ($realNextOffset-$realOffset+1));

                if($next !== '>' or !Strings::isHtmlString($insideAngularTags) ){
                    $html = substr_replace($html, self::LT_PLACEHOLDER, $realOffset, $length);
                    $delta = $delta + strlen(self::LT_PLACEHOLDER) - $length;
                }
            }
        }

        return $html;
    }

    /**
     * @param string $html
     * @param bool $toBeEscaped
     *
     * @return array
     */
    private static function extractHtmlNode( $html, $toBeEscaped = false)
    {
        $pattern = "/<([a-zA-Z0-9._-]+)([^>]|[^<]*?)(([\s]*\/>)|".
                "(>((([^<]*?|<\!\-\-.*?\-\->)|(?R))*)<\/\\1[\s]*>))/sm";
        preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE);

        $elements = [];

        foreach ($matches[0] as $key => $match) {

            $attributes = isset($matches[2][$key][0]) ? self::getAttributes($matches[2][$key][0]) : [];
            $base64Decoded = (isset($attributes['equiv-text'])) ? base64_decode(str_replace("base64:", "", $attributes['equiv-text'])): null;
            $tagName = $matches[1][$key][0];
            $text = (isset($matches[6][$key][0]) and '' !== $matches[6][$key][0]) ? $matches[6][$key][0] : null;
            $originalText = $text;
            $strippedText = strip_tags($text);

            // get start and end tags
            $explodedNode = explode(self::ORIGINAL_TEXT_PLACEHOLDER, str_replace($originalText, self::ORIGINAL_TEXT_PLACEHOLDER, $match[0]));

            $start = (isset($explodedNode[0])) ? $explodedNode[0] : null;
            $end = (isset($explodedNode[1])) ? $explodedNode[1] : null;

            // inner_html
            $inner_html = self::getInnerHtml($matches, $key, $toBeEscaped);

            // node
            $node = self::rebuildNode($originalText, $toBeEscaped, $start, $end);

            $elements[] = (object)[
                'node' => self::restoreLessThanSymbols($node),
                'start' => self::restoreLessThanSymbols($start),
                'end' => self::restoreLessThanSymbols($end),
                'terminator' => ($toBeEscaped) ? '&gt;' : '>',
                'offset' => $match[1],
                'tagname' => $tagName,
                'attributes' => $attributes,
                'base64_decoded' => $base64Decoded,
                'omittag' => ($matches[4][$key][1] > -1), // boolean
                'inner_html' => $inner_html,
                'has_children' => is_array($inner_html),
                'original_text' => ($toBeEscaped) ? self::restoreLessThanSymbols(Strings::escapeOnlyHTMLTags($originalText)) : self::restoreLessThanSymbols($originalText),
                'stripped_text' => self::restoreLessThanSymbols($strippedText),
            ];
        }

        return $elements;
    }

    /**
     * @param $text
     *
     * @return string|string[]
     */
    private static function restoreLessThanSymbols( $text)
    {
        return str_replace(self::LT_PLACEHOLDER, '<', $text);
    }

    /**
     * @param string $originalText
     * @param bool $toBeEscaped
     * @param null $start
     * @param null $end
     *
     * @return string
     */
    private static function rebuildNode($originalText, $toBeEscaped, $start = null, $end = null)
    {
        $node = '';

        if($start !== null){
            $node .= ($toBeEscaped) ? Strings::escapeOnlyHTMLTags($start) : $start;
        }

        $node .= ($toBeEscaped) ? Strings::escapeOnlyHTMLTags($originalText) : $originalText;

        if($end !== null){
            $node .= ($toBeEscaped) ? Strings::escapeOnlyHTMLTags($end) : $end;
        }

        return $node;
    }

    /**
     * @param $content
     *
     * @return mixed
     */
    public static function getAttributes($content)
    {
        $pattern = '/(.*?)=("|\'|\\\")(.*?)("|\'|\\\"|\\\')/';

        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        $attributes = [];

        if (isset($matches[1]) and count($matches[1]) > 0) {
            foreach ($matches[1] as $key => $match) {
                $attributes[trim($match[0])] = $matches[3][$key][0];
            }
        }

        return $attributes;
    }

    /**
     * @param array  $matches
     * @param string $key
     *
     * @param bool   $toBeEscaped
     *
     * @return array|mixed|string
     */
    private static function getInnerHtml($matches, $key, $toBeEscaped = false)
    {
        if (isset($matches[6][$key][0])) {
            $node = self::extractHtmlNode($matches[6][$key][0], $toBeEscaped);

            return (!empty($node)) ? $node : $matches[6][$key][0];
        }

        return null;
    }
}
