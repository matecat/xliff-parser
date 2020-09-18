<?php

namespace Matecat\XliffParser;

use Matecat\XliffParser\XliffParser\XliffParserFactory;
use Matecat\XliffParser\XliffReplacer\XliffReplacerCallbackInterface;
use Matecat\XliffParser\XliffReplacer\XliffReplacerFactory;
use Matecat\XliffParser\XliffUtils\XmlParser;
use Psr\Log\LoggerInterface;

class XliffParser
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * XliffParser constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Replace the translation in a xliff file
     *
     * @param string $originalXliffPath
     * @param array $data
     * @param array $transUnits
     * @param string $targetLang
     * @param string $outputFile
     * @param bool $setSourceInTarget
     * @param XliffReplacerCallbackInterface|null $callback
     */
    public function replaceTranslation($originalXliffPath, &$data, &$transUnits, $targetLang, $outputFile, $setSourceInTarget = false, XliffReplacerCallbackInterface $callback = null)
    {
        try {
            $parser = XliffReplacerFactory::getInstance($originalXliffPath, $data, $transUnits, $targetLang, $outputFile, $setSourceInTarget,  $this->logger, $callback);
            $parser->replaceTranslation();
        } catch (\Exception $exception) {
            // do nothing
        }
    }

    /**
     * Parse a xliff file to array
     *
     * @param string $xliffContent
     *
     * @return array
     */
    public function xliffToArray($xliffContent)
    {
        try {
            $xliff = [];
            $xliffContent = self::forceUft8Encoding($xliffContent, $xliff);
            $parser = XliffParserFactory::getInstance($xliffContent, $this->logger);
            $dom = XmlParser::parse($xliffContent);

            return $parser->parse($dom, $xliff);
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * Pre-Processing.
     * Fixing non UTF-8 encoding (often I get Unicode UTF-16)
     *
     * @param $xliffContent
     * @param $xliff
     *
     * @return false|string
     */
    protected static function forceUft8Encoding($xliffContent, &$xliff)
    {
        $enc = mb_detect_encoding($xliffContent);

        if ($enc !== 'UTF-8') {
            $xliff[ 'parser-warnings' ][] = "Input identified as $enc ans converted UTF-8. May not be a problem if the content is English only";

            return iconv($enc, 'UTF-8', $xliffContent);
        }

        return $xliffContent;
    }
}
