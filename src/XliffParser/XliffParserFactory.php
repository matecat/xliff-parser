<?php

namespace Matecat\XliffParser\XliffParser;

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
    public static function getInstance($version, LoggerInterface $logger = null)
    {
        $parserClass = 'Matecat\\XliffParser\\XliffParser\\XliffParserV' . $version;

        /** @var AbstractXliffParser $parser */
        return new $parserClass($version, $logger);
    }
}
