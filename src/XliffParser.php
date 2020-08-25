<?php

namespace Matecat\XliffParser;

use Matecat\XliffParser\XliffUtils\VersionDetector;
use Matecat\XliffParser\Parser\AbstractParser;
use Matecat\XliffParser\XliffUtils\XmlParser;

class XliffParser
{
    /**
     * @param string $xliffContent
     *
     * @return array
     */
    public function toArray($xliffContent)
    {
        try {
            $xliff = [];
            $xliffContent = $this->forceUft8Encoding($xliffContent, $xliff);
            $version = VersionDetector::detect($xliffContent);
            $parserClass = 'Matecat\\XliffParser\\Parser\\ParserV' . $version;

            /** @var AbstractParser $parser */
            $parser = new $parserClass();
            $dom = XmlParser::parse($xliffContent);

            return $parser->parse($dom, $xliff);
        } catch (\Exception $exception){
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
    protected function forceUft8Encoding($xliffContent, &$xliff)
    {
        $enc = mb_detect_encoding( $xliffContent );

        if ( $enc !== 'UTF-8' ) {
            $xliff[ 'parser-warnings' ][] = "Input identified as $enc ans converted UTF-8. May not be a problem if the content is English only";

            return iconv( $enc, 'UTF-8', $xliffContent );
        }

        return $xliffContent;
    }
}
