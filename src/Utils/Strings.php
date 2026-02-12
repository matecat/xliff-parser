<?php

namespace Matecat\XliffParser\Utils;

use Exception;
use Matecat\XliffParser\Constants\XliffTags;
use SimpleXMLElement;

use function htmlspecialchars_decode;

class Strings
{
    private static ?string $find_xliff_tags_reg = null;
    private static string $htmlEntityRegex = '/&amp;[#a-zA-Z0-9]{1,20};/u';

    /**
     * @throws Exception
     */
    public static function cleanCDATA(string $testString): string
    {
        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $cleanXMLContent = new SimpleXMLElement('<rootNoteNode>' . $testString . '</rootNoteNode>', LIBXML_NOCDATA);
            return $cleanXMLContent->__toString();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
        }
    }

    public static function isJSON(string $string): bool
    {
        if (is_numeric($string)) {
            return false;
        }

        try {
            $string = self::cleanCDATA($string);
        } catch (Exception) {
            return false;
        }

        $string = trim($string);
        if (empty($string)) {
            return false;
        }

        // String representation in JSON is "quoted", but we want to accept only an object or arrays.
        // exclude strings and numbers and other primitive types
        if (in_array($string[0], ["{", "["], true)) {
            return json_validate($string);
        }

        return false; // Not accepted: string or primitive types.
    }

    /**
     * @return array<string, mixed>
     */
    public static function jsonToArray(string $string): array
    {
        $decodedJSON = json_decode($string, true);

        return is_array($decodedJSON) ? $decodedJSON : [];
    }

    /**
     * This function exists because many developers started adding html tags directly into the XLIFF source since:
     * 1) XLIFF tag remapping is too complex for them
     * 2) Trados does not lock Tags within the <source> that are expressed as &gt;b&lt; but is tolerant to html tags in <source>
     *
     * in short people typed:
     * <source>The <b>red</d> house</source> or worst <source>5 > 3</source>
     * instead of
     * <source>The <g id="1">red</g> house.</source> and <source>5 &gt; 3</source>
     *
     * This function will do the following
     * <g id="1">Hello</g>, 4 > 3 -> <g id="1">Hello</g>, 4 &gt; 3
     * <g id="1">Hello</g>, 4 > 3 &gt; -> <g id="1">Hello</g>, 4 &gt; 3 &gt; 2
     */
    public static function fixNonWellFormedXml(string $content, ?bool $escapeStrings = true): string
    {
        if (self::$find_xliff_tags_reg === null) {
            // Convert the list of tags in a regexp list, for example "g|x|bx|ex"
            $xliffTags = XliffTags::$tags;
            $xliff_tags_reg_list = implode('|', $xliffTags);
            // Regexp to find all the XLIFF tags:
            //   </?               -> matches the tag start, for both opening and
            //                        closure tags (see the optional slash)
            //   ($xliff_tags_reg) -> matches one of the XLIFF tags in the list above
            //   (\s[^>]*)?        -> matches attributes and so on; ensures there's a
            //                        space after the tag, to not confuse for example a
            //                        "g" tag with a "gblabla"; [^>]* matches anything,
            //                        including additional spaces; the entire block is
            //                        optional, to allow tags with no spaces or attrs
            //   /? >              -> matches tag end, with optional slash for
            //                        self-closing ones
            // If you are wondering about spaces inside tags, look at this:
            // http://www.w3.org/TR/REC-xml/#sec-starttags
            // It says that there cannot be any space between the '<' and the tag name,
            // between '</' and the tag name, or inside '/>'. But you can add white
            // space after the tag name, though.
            self::$find_xliff_tags_reg = "#</?($xliff_tags_reg_list)(\\s[^>]*)?/?>#si";
        }

        // Find all the XLIFF tags
        preg_match_all(self::$find_xliff_tags_reg, $content, $matches);
        $tags = $matches[0];

        // Prepare placeholders
        $tags_placeholders = [];
        $tagsNum = count($tags);
        for ($i = 0; $i < $tagsNum; $i++) {
            $tag = $tags[$i];
            $tags_placeholders[$tag] = "#@!XLIFF-TAG-$i!@#";
        }

        // Replace all XLIFF tags with placeholders that will not be escaped
        foreach ($tags_placeholders as $tag => $placeholder) {
            $content = str_replace($tag, $placeholder, $content);
        }

        // Escape the string with the remaining non-XLIFF tags
        if ($escapeStrings) {
            $content = htmlspecialchars($content, ENT_NOQUOTES, 'UTF-8', false);
        }

        // Put again in place the original XLIFF tags replacing placeholders
        foreach ($tags_placeholders as $tag => $placeholder) {
            $content = str_replace($placeholder, $tag, $content);
        }

        return $content;
    }

    public static function removeDangerousChars(?string $string): string
    {
        // clean invalid xml entities ( characters with ascii < 32 and different from 0A, 0D and 09
        $regexpEntity = '/&#x(0[0-8BCEF]|1[\dA-F]|7F);/u';

        // remove binary chars in some xliff files
        $regexpAscii = '/[\x{00}-\x{08}\x{0B}\x{0C}\x{0E}-\x{1F}\x{7F}]/u';

        $string = preg_replace($regexpAscii, '', $string ?? '');
        $string = preg_replace($regexpEntity, '', $string ?? '');

        return !empty($string) || strlen($string) > 0 ? $string : "";
    }

    public static function htmlspecialchars_decode(string $string, ?bool $onlyEscapedEntities = false): string
    {
        if ($onlyEscapedEntities === false) {
            return htmlspecialchars_decode($string, ENT_NOQUOTES);
        }

        return (string)preg_replace_callback(
            self::$htmlEntityRegex,
            static fn(array $match): string => self::htmlspecialchars_decode($match[0]),
            $string
        );
    }

    /**
     * Checks if a string is a double encoded entity.
     *
     * Example:
     *
     * &amp;#39; ---> true
     * &#39;     ---> false
     */
    public static function isADoubleEscapedEntity(string $str): bool
    {
        return preg_match(self::$htmlEntityRegex, $str) !== 0;
    }

    public static function isAValidUuidV4(string $uuid): bool
    {
        return preg_match('/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}$/', $uuid) === 1;
    }

    /**
     * @return list<string>|false
     */
    public static function preg_split(string $pattern, string $subject): array|false
    {
        return preg_split($pattern, $subject, -1, PREG_SPLIT_NO_EMPTY);
    }

    public static function getTheNumberOfTrailingSpaces(string $segment): int
    {
        return mb_strlen($segment) - mb_strlen(rtrim($segment, ' '));
    }

}
