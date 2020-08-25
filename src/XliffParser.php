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
            $version = VersionDetector::detect($xliffContent);
            $parserClass = 'Matecat\\XliffParser\\Parser\\ParserV' . $version;

            /** @var AbstractParser $parser */
            $parser = new $parserClass();
            $dom = XmlParser::parse($xliffContent);

            return $parser->parse($dom);
        } catch (\Exception $exception){
            return [];
        }
    }
}
