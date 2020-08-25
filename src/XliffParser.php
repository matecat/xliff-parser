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
            $dom = XmlParser::parse($xliffContent);
            $version = VersionDetector::detect($dom);
            $parserClass = 'Matecat\\XliffParser\\Parser\\ParserV' . $version;

            /** @var AbstractParser $parser */
            $parser = new $parserClass();

            return $parser->parse($dom);
        } catch (\Exception $exception){
            return [];
        }
    }
}
