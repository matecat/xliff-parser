<?php

namespace Matecat\XliffParser\XliffParser;

use Matecat\XliffParser\XliffUtils\XliffVersionDetector;
use Psr\Log\LoggerInterface;

class XliffParserFactory
{
    /**
     * @param $xliffContent
     *
     * @return AbstractXliffParser
     * @throws \Matecat\XliffParser\Exception\NotSupportedVersionException
     * @throws \Matecat\XliffParser\Exception\NotValidFileException
     */
    public static function getInstance($xliffContent, LoggerInterface $logger = null)
    {
        $version = XliffVersionDetector::detect($xliffContent);
        $parserClass = 'Matecat\\XliffParser\\XliffParser\\XliffParserV' . $version;

        /** @var AbstractXliffParser $parser */
        return new $parserClass($logger);
    }
}
