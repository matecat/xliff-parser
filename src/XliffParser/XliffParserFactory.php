<?php

namespace Matecat\XliffParser\XliffParser;

use Psr\Log\LoggerInterface;

class XliffParserFactory
{
    /**
     * @param int                  $xliffVersion
     * @param string|null          $xliffProprietary
     * @param LoggerInterface|null $logger
     *
     * @return mixed
     */
    public static function getInstance($xliffVersion, $xliffProprietary = null, LoggerInterface $logger = null)
    {
        $parserClass = 'Matecat\\XliffParser\\XliffParser\\XliffParserV' . $xliffVersion;

        /** @var AbstractXliffParser $parser */
        return new $parserClass($xliffVersion, $xliffProprietary, $logger);
    }
}
