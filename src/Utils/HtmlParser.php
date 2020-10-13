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
        $pattern = "/<([\w]+)([^>]*?)(([\s]*\/>)|".
                "(>((([^<]*?|<\!\-\-.*?\-\->)|(?R))*)<\/\\1[\s]*>))/sm";
        preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE);

        $elements = [];

        foreach ($matches[0] as $key => $match) {
            $elements[] = (object)[
                'node' => ($toBeEscaped) ? Strings::htmlentities($match[0]) : $match[0],
                'offset' => $match[1],
                'tagname' => $matches[1][$key][0],
                'attributes' => isset($matches[2][$key][0]) ? self::getAttributes($matches[2][$key][0]) : [],
                'omittag' => ($matches[4][$key][1] > -1), // boolean
                'inner_html' => $inner_html = self::getInnerHtml($matches, $key, $toBeEscaped),
                'has_children' => is_array($inner_html),
            ];
        }

        return $elements;
    }

    /**
     * @param $content
     *
     * @return mixed
     */
    private static function getAttributes($content)
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

            return (!empty($node)) ? $node: $matches[6][$key][0];
        }

        return '';
    }
}
