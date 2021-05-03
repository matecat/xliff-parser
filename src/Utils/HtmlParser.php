<?php

namespace Matecat\XliffParser\Utils;

class HtmlParser
{
    /**
     * This solution is taken from here and modified:
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

        return self::extractHtmlNode($html, $toBeEscaped);
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
            $explosionPlaceholder = '#####__ORIGINAL_TEXT__#####';
            $explodedNode = explode($explosionPlaceholder, str_replace($originalText, $explosionPlaceholder, $match[0]));

            $start = (isset($explodedNode[0])) ? $explodedNode[0] : null;
            $end = (isset($explodedNode[1])) ? $explodedNode[1] : null;

            // inner_html
            $inner_html = self::getInnerHtml($matches, $key, $toBeEscaped);

            // node
            $node = self::rebuildNode($originalText, $toBeEscaped, $start, $end);

            $elements[] = (object)[
                'node' => $node,
                'start' => $start,
                'end' => $end,
                'terminator' => ($toBeEscaped) ? '&gt;' : '>',
                'offset' => $match[1],
                'tagname' => $tagName,
                'attributes' => $attributes,
                'base64_decoded' => $base64Decoded,
                'omittag' => ($matches[4][$key][1] > -1), // boolean
                'inner_html' => $inner_html,
                'has_children' => is_array($inner_html),
                'original_text' => ($toBeEscaped) ? Strings::escapeOnlyHTMLTags($originalText) : $originalText,
                'stripped_text' => $strippedText,
            ];
        }

        return $elements;
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
