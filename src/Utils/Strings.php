<?php

namespace Matecat\XliffParser\Utils;

use Matecat\XliffParser\Exception\NotValidJSONException;

class Strings
{
    private static $find_xliff_tags_reg = null;

    /**
     * @param string $testString
     *
     * @return string
     */
    public static function cleanCDATA($testString)
    {
        $cleanXMLContent = new \SimpleXMLElement('<rootNoteNode>' . $testString . '</rootNoteNode>', LIBXML_NOCDATA);

        return $cleanXMLContent->__toString();
    }

    /**
     * @param $string
     *
     * @return bool
     * @throws \Exception
     */
    public static function isJSON($string)
    {
        try {
            $string = Strings::cleanCDATA($string);

            if (empty($string)) {
                throw new \Exception();
            }

            json_decode($string);
            self::raiseJsonExceptionError();
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * @param bool $raise
     *
     * @return string|null
     * @throws NotValidJSONException
     */
    private static function raiseJsonExceptionError($raise = true)
    {
        if (function_exists("json_last_error")) {
            $error = json_last_error();

            switch ($error) {
                case JSON_ERROR_NONE:
                    $msg = null; # - No errors
                    break;
                case JSON_ERROR_DEPTH:
                    $msg = ' - Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $msg = ' - Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $msg = ' - Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $msg = ' - Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $msg = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    $msg = ' - Unknown error';
                    break;
            }

            if ($raise && $error != JSON_ERROR_NONE) {
                throw new NotValidJSONException($msg, $error);
            } elseif ($error != JSON_ERROR_NONE) {
                return $msg;
            }
        }
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
     *
     * @param string $content
     *
     * @return mixed|string
     */
    public static function fixNonWellFormedXml($content)
    {
        if (self::$find_xliff_tags_reg === null) {
            // List of the tags that we don't want to escape
            $xliff_tags = [ 'g', 'x', 'bx', 'ex', 'bpt', 'ept', 'ph', 'it', 'mrk' ];
            // Convert the list of tags in a regexp list, for example "g|x|bx|ex"
            $xliff_tags_reg_list = implode('|', $xliff_tags);
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
        $tags = (array)$matches[ 0 ];

        // Prepare placeholders
        $tags_placeholders = [];
        for ($i = 0; $i < count($tags); $i++) {
            $tag                       = $tags[ $i ];
            $tags_placeholders[ $tag ] = "#@!XLIFF-TAG-$i!@#";
        }

        // Replace all XLIFF tags with placeholders that will not be escaped
        foreach ($tags_placeholders as $tag => $placeholder) {
            $content = str_replace($tag, $placeholder, $content);
        }

        // Escape the string with the remaining non-XLIFF tags
        $content = htmlspecialchars($content, ENT_NOQUOTES, 'UTF-8', false);

        // Put again in place the original XLIFF tags replacing placeholders
        foreach ($tags_placeholders as $tag => $placeholder) {
            $content = str_replace($placeholder, $tag, $content);
        }

        return $content;
    }

    /**
     * @param $string
     *
     * @return string|string[]|null
     */
    public static function removeDangerousChars($string)
    {
        // clean invalid xml entities ( characters with ascii < 32 and different from 0A, 0D and 09
        $regexpEntity = '/&#x(0[0-8BCEF]|1[0-9A-F]|7F);/u';

        // remove binary chars in some xliff files
        $regexpAscii  = '/[\x{00}-\x{08}\x{0B}\x{0C}\x{0E}-\x{1F}\x{7F}]/u';

        $string = preg_replace($regexpAscii, '', $string);
        $string = preg_replace($regexpEntity, '', $string);

        return $string;
    }
}
