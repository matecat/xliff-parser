<?php

namespace Matecat\XliffParser;

use Exception;
use Matecat\XliffParser\Constants\Placeholder;
use Matecat\XliffParser\Constants\XliffTags;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\Utils\Strings;
use Matecat\XliffParser\XliffParser\XliffParserFactory;
use Matecat\XliffParser\XliffReplacer\XliffReplacerCallbackInterface;
use Matecat\XliffParser\XliffReplacer\XliffReplacerFactory;
use Matecat\XliffParser\XliffUtils\XliffProprietaryDetect;
use Matecat\XliffParser\XliffUtils\XliffVersionDetector;
use Matecat\XmlParser\Config;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use Matecat\XmlParser\XmlDomLoader;
use Psr\Log\LoggerInterface;

class XliffParser {
    /**
     * @var ?LoggerInterface
     */
    private ?LoggerInterface $logger;

    /**
     * XliffParser constructor.
     *
     * @param ?LoggerInterface $logger
     */
    public function __construct( ?LoggerInterface $logger = null ) {
        $this->logger = $logger;
    }

    /**
     * Replace the translation in a xliff file
     *
     * @param string                              $originalXliffPath
     * @param array                               $data
     * @param array                               $transUnits
     * @param string                              $targetLang
     * @param string                              $outputFile
     * @param bool                                $setSourceInTarget
     * @param XliffReplacerCallbackInterface|null $callback
     */
    public function replaceTranslation( string $originalXliffPath, array $data, array $transUnits, string $targetLang, string $outputFile, bool $setSourceInTarget = false, ?XliffReplacerCallbackInterface $callback = null ) {
        try {
            $parser = XliffReplacerFactory::getInstance( $originalXliffPath, $data, $transUnits, $targetLang, $outputFile, $setSourceInTarget, $this->logger, $callback );
            $parser->replaceTranslation();
        } catch ( Exception $exception ) {
            // do nothing
        }
    }

    /**
     * Parse an xliff file to array
     *
     * @param string $xliffContent
     *
     * @param bool   $collapseEmptyTags
     *
     * @return array
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    public function xliffToArray( string $xliffContent, ?bool $collapseEmptyTags = false ): array {
        $xliff        = [];
        $xliffContent = self::forceUft8Encoding( $xliffContent, $xliff );
        $xliffVersion = XliffVersionDetector::detect( $xliffContent );
        $info         = XliffProprietaryDetect::getInfoFromXliffContent( $xliffContent );

        if ( $xliffVersion === 1 ) {
            $xliffContent = self::removeInternalFileTagFromContent( $xliffContent, $xliff );
        }

        if ( $xliffVersion === 2 ) {
            $xliffContent = self::escapeDataInOriginalMap( $xliffContent );
        }

        if ( $collapseEmptyTags === false ) {
            $xliffContent = self::insertPlaceholderInEmptyTags( $xliffContent );
        }

        $xliffProprietary = $info[ 'proprietary_short_name' ] ?? null;
        $parser           = XliffParserFactory::getInstance( $xliffVersion, $xliffProprietary, $this->logger );

        $dom = XmlDomLoader::load(
                $xliffContent,
                new Config(
                        null,
                        false,
                        LIBXML_NONET | LIBXML_PARSEHUGE
                )
        );

        return $parser->parse( $dom, $xliff );
    }

    /**
     * Pre-Processing.
     * Fixing non UTF-8 encoding (often I get Unicode UTF-16)
     *
     * @param $xliffContent
     * @param $xliff
     *
     * @return string
     */
    private static function forceUft8Encoding( $xliffContent, &$xliff ): string {
        $enc = mb_detect_encoding( $xliffContent );

        if ( $enc !== 'UTF-8' ) {
            $xliff[ 'parser-warnings' ][] = "Input identified as $enc ans converted UTF-8. May not be a problem if the content is English only";
            $s                            = iconv( $enc, 'UTF-8', $xliffContent );
            $xliffContent                 = $s !== false ? $s : "";
        }

        return $xliffContent;
    }

    /**
     * Remove <internal-file> heading tag from xliff content
     * This allows to parse xliff files with a very large <internal-file>
     * (only for Xliff 1.0)
     *
     * @param $xliffContent
     * @param $xliff
     *
     * @return mixed|string
     */
    private static function removeInternalFileTagFromContent( $xliffContent, &$xliff ) {
        $index       = 1;
        $a           = Strings::preg_split( '|<internal-file[\s>]|i', $xliffContent );
        $tagMatches  = count( $a );

        // no match, return original string
        if ( $tagMatches === 1 ) {
            return $a[ 0 ];
        }

        $b                                           = Strings::preg_split( '|</internal-file>|i', $a[ 1 ] );
        $strippedContent                             = $a[ 0 ] . $b[ 1 ];
        $xliff[ 'files' ][ $index ][ 'reference' ][] = self::extractBase64( $b[ 0 ] );
        $index++;

        // Sometimes, sdlxliff files can contain more than 2 <internal-file> nodes.
        // In this case loop and extract any other extra <internal-file> node
        for($i=2; $i < $tagMatches; $i++){
            if ( isset( $a[ $i ] ) ) {
                $c                                           = Strings::preg_split( '|</internal-file[\s>]|i', $a[ $i ] );
                $strippedContent                             .= $c[ 1 ];
                $xliff[ 'files' ][ $index ][ 'reference' ][] = self::extractBase64( $c[ 0 ] );
            }
        }

        return $strippedContent;
    }

    /**
     * @param $base64
     *
     * @return array
     */
    private static function extractBase64( $base64 ): array {
        return [
                'form-type' => 'base64',
                'base64'    => trim( str_replace( 'form="base64">', '', $base64 ) ),
        ];
    }

    /**
     * This function replaces:
     *
     * - spaces (like white space, tab space etc..)
     * - xliff tags (see XliffTags::$tags for the full list)
     *
     * with placeholders in the <original-data> map to preserve them as they are.
     *
     * XliffParserV2::extractTransUnitOriginalData function will restore them
     *
     * (only for Xliff 2.0)
     *
     * @param $xliffContent
     *
     * @return string
     */
    private static function escapeDataInOriginalMap( string $xliffContent ): string {
        $xliffContent = preg_replace_callback( '|<data(.*?)>(.*?)</data>|iU', [ XliffParser::class, 'replaceSpace' ], $xliffContent );
        $xliffContent = preg_replace_callback( '|<data(.*?)>(.*?)</data>|iU', [ XliffParser::class, 'replaceXliffTags' ], $xliffContent );

        return $xliffContent;
    }

    /**
     * Insert a placeholder inside empty tags
     * in order to prevent they are collapsed by parser
     *
     * Example:
     *
     * <pc id="12" dataRefStart="d1"></pc> ---> <pc id="12" dataRefStart="d1">###___EMPTY_TAG_PLACEHOLDER___###</pc>
     *
     * AbstractXliffParser::extractTagContent() will cut out ###___EMPTY_TAG_PLACEHOLDER___### to restore original empty tags
     *
     * @param $xliffContent
     *
     * @return string
     */
    private static function insertPlaceholderInEmptyTags( $xliffContent ): string {
        preg_match_all( '|<([a-zA-Z0-9._-]+)[^>]*></\1>|m', $xliffContent, $emptyTagMatches );

        if ( !empty( $emptyTagMatches[ 0 ] ) ) {
            foreach ( $emptyTagMatches[ 0 ] as $index => $emptyTagMatch ) {
                $matchedTag   = $emptyTagMatches[ 1 ][ $index ];
                $subst        = Placeholder::EMPTY_TAG_PLACEHOLDER . '</' . $matchedTag . '>';
                $replacedTag  = str_replace( '</' . $matchedTag . '>', $subst, $emptyTagMatch );
                $xliffContent = str_replace( $emptyTagMatch, $replacedTag, $xliffContent );
            }
        }

        return $xliffContent;
    }

    /**
     * Replace <data> value
     *
     * @param array $matches
     *
     * @return string
     */
    private static function replaceSpace( array $matches ): string {
        $content = str_replace( ' ', Placeholder::WHITE_SPACE_PLACEHOLDER, $matches[ 2 ] );
        $content = str_replace( '\n', Placeholder::NEW_LINE_PLACEHOLDER, $content );
        $content = str_replace( '\t', Placeholder::TAB_PLACEHOLDER, $content );

        return '<data' . $matches[ 1 ] . '>' . $content . '</data>';
    }

    /**
     * @param array $matches
     *
     * @return string
     */
    private static function replaceXliffTags( array $matches ): string {
        $xliffTags = XliffTags::$tags;
        $content   = $matches[ 2 ];

        foreach ( $xliffTags as $xliffTag ) {
            $content = preg_replace( '|&lt;(' . $xliffTag . '.*?)&gt;|si', Placeholder::LT_PLACEHOLDER . "$1" . Placeholder::GT_PLACEHOLDER, $content );
            $content = preg_replace( '|&lt;(/' . $xliffTag . ')&gt;|si', Placeholder::LT_PLACEHOLDER . "$1" . Placeholder::GT_PLACEHOLDER, $content );
        }

        return '<data' . $matches[ 1 ] . '>' . $content . '</data>';
    }
}
