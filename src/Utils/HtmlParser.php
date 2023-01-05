<?php

namespace Matecat\XliffParser\Utils;

class HtmlParser
{
    const ORIGINAL_TEXT_PLACEHOLDER = '#####__ORIGINAL_TEXT__#####';
    const LT_PLACEHOLDER = '#####__LT_PLACEHOLDER__#####';
    const GT_PLACEHOLDER = '#####__GT_PLACEHOLDER__#####';

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

        $html = self::protectNotClosedHtmlTags($html);
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
        $realNextOffset = 0;
        $next = null;

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

            if( $current === '<' && isset($next)){

                // 1. if next is > or
                // 2. next is < and is not html tag (like < >)
                $insideAngularTags = substr($html, $realOffset, ($realNextOffset-$realOffset+1));

                if($next !== '>' || !Strings::isHtmlString($insideAngularTags) ){
                    $html = substr_replace($html, self::LT_PLACEHOLDER, $realOffset, $length);
                    $delta = $delta + strlen(self::LT_PLACEHOLDER) - $length;
                }
            }
        }

        return $html;
    }

    /**
     * Protect not closed html tags.
     *
     * Example:
     *
     * Ciao <div> this div is not closed. <div>Instead, this is a closed div.</div>
     *
     * is converted to:
     *
     * Ciao #####__LT_PLACEHOLDER__#####div#####__GT_PLACEHOLDER__##### this div is not closed. <div>Instead, this is a closed div.</div>
     *
     * @param string $html
     *
     * @return string
     */
    private static function protectNotClosedHtmlTags( $html)
    {
        preg_match_all('/<|>/iu', $html, $matches, PREG_OFFSET_CAPTURE);

        $tags = [];
        $offsets = [];
        $originalLengths = [];

        // 1. Map all tags
        foreach ($matches[0] as $key => $match) {
            $current       = $matches[ 0 ][ $key ][ 0 ];
            $currentOffset = $matches[ 0 ][ $key ][ 1 ];

            // check every string inside angular brackets (< and >)
            if( $current === '<' && isset($matches[0][$key+1][0]) && $matches[0][$key+1][0] === '>' ){
                $nextOffset = $matches[0][$key+1][1];
                $tag = substr($html, ($currentOffset + 1), ( $nextOffset - $currentOffset - 1 ));
                $trimmedTag = trim($tag);

                // if the tag is self closed do nothing
                if(Strings::lastChar($tag) !== '/'){
                    $tags[] = $trimmedTag;
                    $offsets[] = $currentOffset;
                    $originalLengths[] = strlen($tag) + 2; // add 2 to length because there are < and >
                }
            }
        }

        // 2. Removing closed tags
        $indexes = [];

        if(count($tags) > 0){
            foreach ($tags as $index => $tag){

                if(Strings::contains('/', $tag)){
                    $complementaryTag = $tag;
                } else {
                    $complementaryTag = '/'.explode(' ', $tag)[0];
                }

                $complementaryTagIndex = array_search($complementaryTag, $tags);

                if(false !== $complementaryTagIndex){
                    $indexes[] = $index;
                    $indexes[] = $complementaryTagIndex;
                }
            }
        }

        $indexes = array_unique($indexes);
        foreach ($indexes as $index){
            unset($tags[$index]);
        }

        // 3. Loop not closed tags
        $delta = 0;

        if(count($tags)){
            foreach ($tags as $index => $tag){

                $length = $originalLengths[$index];
                $offset = $offsets[$index];
                $realOffset = ($delta === 0) ? $offset : ($offset + $delta);

                $replacement = self::LT_PLACEHOLDER . $tag . self::GT_PLACEHOLDER;

                $html = substr_replace($html, $replacement, $realOffset, $length);
                $delta = $delta + strlen($replacement) - $length;
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
            $text = (isset($matches[6][$key][0]) && '' !== $matches[6][$key][0]) ? $matches[6][$key][0] : null;
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

            // terminator
            $terminator = ($toBeEscaped) ? '&gt;' : '>';

            // self closed
            $selfClosed = Strings::contains('/>', trim($start));

            $elements[] = (object)[
                'node' => self::restoreLessThanAndGreaterThanSymbols($node),
                'start' => self::restoreLessThanAndGreaterThanSymbols($start),
                'end' => self::restoreLessThanAndGreaterThanSymbols($end),
                'terminator' => $terminator,
                'offset' => $match[1],
                'tagname' => $tagName,
                'attributes' => $attributes,
                'base64_decoded' => $base64Decoded,
                'self_closed' => $selfClosed,
                'omittag' => ($matches[4][$key][1] > -1), // boolean
                'inner_html' => $inner_html,
                'has_children' => is_array($inner_html),
                'original_text' => ($toBeEscaped) ? self::restoreLessThanAndGreaterThanSymbols(Strings::escapeOnlyHTMLTags($originalText)) : self::restoreLessThanAndGreaterThanSymbols($originalText),
                'stripped_text' => self::restoreLessThanAndGreaterThanSymbols($strippedText),
            ];
        }

        return $elements;
    }

    /**
     * @param $text
     *
     * @return string|string[]
     */
    private static function restoreLessThanAndGreaterThanSymbols( $text)
    {
        return str_replace([self::LT_PLACEHOLDER, self::GT_PLACEHOLDER], ['<','>'], $text);
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

        if (isset($matches[1]) && count($matches[1]) > 0) {
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
