<?php

namespace Matecat\XliffParser\XliffParser;

use Matecat\XliffParser\XliffUtils\XliffVersionDetector;

class XliffParserFactory
{
    /**
     * @param $xliffContent
     *
     * @return AbstractXliffParser
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     * @throws \Matecat\XliffParser\Exception\NotValidFileException
     */
    public static function getInstance($xliffContent)
    {
        $version = XliffVersionDetector::detect($xliffContent);
        $parserClass = 'Matecat\\XliffParser\\XliffParser\\XliffParserV' . $version;

        /** @var AbstractXliffParser $parser */
        return new $parserClass();
    }
}
