<?php

namespace Matecat\XliffParser\Parser;

use Matecat\XliffParser\XliffUtils\XliffVersionDetector;

class ParserFactory
{
    /**
     * @param $xliffContent
     *
     * @return AbstractParser
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     * @throws \Matecat\XliffParser\Exception\NotValidFileException
     */
    public static function getInstance($xliffContent)
    {
        $version = XliffVersionDetector::detect($xliffContent);
        $parserClass = 'Matecat\\XliffParser\\Parser\\ParserV' . $version;

        /** @var AbstractParser $parser */
        return new $parserClass();
    }
}