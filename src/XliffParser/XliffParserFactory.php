<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffParser;

use Psr\Log\LoggerInterface;

final class XliffParserFactory
{

    /**
     * Create parser instance for specified XLIFF version.
     */
    public static function getInstance(
        int $xliffVersion,
        ?string $xliffProprietary = null,
        ?LoggerInterface $logger = null
    ): AbstractXliffParser {
        return match ($xliffVersion) {
            1 => new XliffParserV1($xliffVersion, $xliffProprietary, $logger),
            2 => new XliffParserV2($xliffVersion, $xliffProprietary, $logger),
            default => throw new \InvalidArgumentException("Unsupported XLIFF version: $xliffVersion"),
        };
    }
}
