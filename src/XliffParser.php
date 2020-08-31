<?php

namespace Matecat\XliffParser;

use Matecat\XliffParser\Parser\ParserFactory;
use Matecat\XliffParser\XliffUtils\XliffVersionDetector;
use Matecat\XliffParser\Parser\AbstractParser;
use Matecat\XliffParser\XliffUtils\XmlParser;

class XliffParser
{
    public static function arrayToXliff(array $array = [])
    {
    }

    /**
     * @param string $xliffContent
     *
     * @return array
     */
    public static function xliffToArray( $xliffContent)
    {
        try {
            $xliff = [];
            $xliffContent = self::forceUft8Encoding($xliffContent, $xliff);
            $parser = ParserFactory::getInstance($xliffContent);
            $dom = XmlParser::parse($xliffContent);

            return $parser->parse($dom, $xliff);
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * Pre-Processing.
     * Fixing non UTF-8 encoding (often I get Unicode UTF-16)
     *
     * @param $xliffContent
     * @param $xliff
     *
     * @return false|string
     */
    protected static function forceUft8Encoding($xliffContent, &$xliff)
    {
        $enc = mb_detect_encoding($xliffContent);

        if ($enc !== 'UTF-8') {
            $xliff[ 'parser-warnings' ][] = "Input identified as $enc ans converted UTF-8. May not be a problem if the content is English only";

            return iconv($enc, 'UTF-8', $xliffContent);
        }

        return $xliffContent;
    }
}
