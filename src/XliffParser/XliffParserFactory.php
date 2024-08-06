<?php

namespace Matecat\XliffParser\XliffParser;

use Psr\Log\LoggerInterface;

class XliffParserFactory {
    /**
     * @param int                  $xliffVersion
     * @param string|null          $xliffProprietary
     * @param LoggerInterface|null $logger
     *
     * @return AbstractXliffParser
     */
    public static function getInstance( int $xliffVersion, ?string $xliffProprietary = null, LoggerInterface $logger = null ): AbstractXliffParser {
        $parserClass = 'Matecat\\XliffParser\\XliffParser\\XliffParserV' . $xliffVersion;

        /** @var AbstractXliffParser $parser */
        return new $parserClass( $xliffVersion, $xliffProprietary, $logger );
    }
}
