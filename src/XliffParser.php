<?php

namespace Matecat\XliffParser;

use Matecat\XliffParser\Utils\VersionDetector;
use Matecat\XliffParser\Parser\AbstractParser;

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
            $version = (new VersionDetector())->detect($xliffContent);
            $parserClass = 'Matecat\\XliffParser\\Parser\\ParserV' . $version;

            /** @var AbstractParser $parser */
            $parser = new $parserClass();

            return $parser->parse($xliffContent);
        } catch (\Exception $exception){
            return [];
        }
    }
}
