<?php

namespace Matecat\XliffParser\XliffParser;

use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;
use Matecat\EmojiParser\Emoji;
use Matecat\XliffParser\Constants\Placeholder;
use Matecat\XliffParser\Utils\Strings;
use OverflowException;
use Psr\Log\LoggerInterface;

abstract class AbstractXliffParser {

    const MAX_GROUP_RECURSION_LEVEL = 50;

    /**
     * @var LoggerInterface|null
     */
    protected ?LoggerInterface $logger;

    /**
     * @var string|null
     */
    protected ?string $xliffProprietary;

    /**
     * @var int
     */
    protected $xliffVersion;

    /**
     * AbstractXliffParser constructor.
     *
     * @param int                  $xliffVersion
     * @param string|null          $xliffProprietary
     * @param LoggerInterface|null $logger
     */
    public function __construct( int $xliffVersion, ?string $xliffProprietary = null, LoggerInterface $logger = null ) {
        $this->xliffVersion     = $xliffVersion;
        $this->logger           = $logger;
        $this->xliffProprietary = $xliffProprietary;
    }

    /**
     * @return string
     */
    protected function getTuTagName(): string {
        return ( $this->xliffVersion === 1 ) ? 'trans-unit' : 'unit';
    }

    /**
     * @param DOMDocument $dom
     * @param array|null  $output
     *
     * @return array
     */
    abstract public function parse( DOMDocument $dom, ?array $output = [] ): array;

    /**
     * Extract trans-unit content from the current node
     *
     * @param DOMElement  $childNode
     * @param array       $transUnitIdArrayForUniquenessCheck
     * @param DOMDocument $dom
     * @param array       $output
     * @param int         $i
     * @param int         $j
     * @param array|null  $contextGroups
     * @param int|null    $recursionLevel
     */
    protected function extractTuFromNode( DOMNode $childNode, array &$transUnitIdArrayForUniquenessCheck, DOMDocument $dom, array &$output, int &$i, int &$j, ?array $contextGroups = [], ?int $recursionLevel = 0 ) {

        if ( $childNode->nodeType != XML_ELEMENT_NODE ) {
            return;
        }

        if ( $childNode->nodeName === 'group' ) {

            // add nested context-groups
            foreach ( $childNode->childNodes as $nestedChildNode ) {
                if ( $nestedChildNode->nodeName === 'context-group' ) {
                    $contextGroups[] = $nestedChildNode;
                }
            }

            // avoid infinite recursion
            $recursionLevel++;

            foreach ( $childNode->childNodes as $nestedChildNode ) {

                // nested groups
                if ( $nestedChildNode->nodeName === 'group' ) {

                    if ( $recursionLevel < self::MAX_GROUP_RECURSION_LEVEL ) {
                        $this->extractTuFromNode( $nestedChildNode, $transUnitIdArrayForUniquenessCheck, $dom, $output, $i, $j, $contextGroups, $recursionLevel );
                    } else {
                        throw new OverflowException( "Maximum tag group nesting level of '" . self::MAX_GROUP_RECURSION_LEVEL . "' reached, aborting!" );
                    }

                } elseif ( $nestedChildNode->nodeName === $this->getTuTagName() ) {
                    $this->extractTransUnit( $nestedChildNode, $transUnitIdArrayForUniquenessCheck, $dom, $output, $i, $j, $contextGroups );
                }
            }
        } elseif ( $childNode->nodeName === $this->getTuTagName() ) {
            $this->extractTransUnit( $childNode, $transUnitIdArrayForUniquenessCheck, $dom, $output, $i, $j, $contextGroups );
        }
    }

    /**
     * Extract and populate 'trans-units' array
     *
     * @param DOMElement  $transUnit
     * @param array       $transUnitIdArrayForUniquenessCheck
     * @param DOMDocument $dom
     * @param array       $output
     * @param int         $i
     * @param int         $j
     * @param array|null  $contextGroups
     *
     * @return mixed
     */
    abstract protected function extractTransUnit( DOMElement $transUnit, array &$transUnitIdArrayForUniquenessCheck, DomDocument $dom, array &$output, int &$i, int &$j, ?array $contextGroups = [] );

    /**
     * @param DOMDocument $dom
     * @param DOMElement  $node
     *
     * @return array
     */
    protected function extractContent( DOMDocument $dom, DOMNode $node ): array {
        return [
                'raw-content' => $this->extractTagContent( $dom, $node ),
                'attr'        => $this->extractTagAttributes( $node )
        ];
    }

    /**
     * Extract attributes if they are present
     *
     * Ex:
     * <p align=center style="font-size: 12px;">some text</p>
     *
     * $attr->nodeName == 'align' :: $attr->nodeValue == 'center'
     * $attr->nodeName == 'style' :: $attr->nodeValue == 'font-size: 12px;'
     *
     * @param DOMNode $element
     *
     * @return array
     */
    protected function extractTagAttributes( DOMNode $element ): array {
        $tagAttributes = [];

        if ( $element->hasAttributes() ) {
            foreach ( $element->attributes as $attr ) {
                $tagAttributes[ $attr->nodeName ] = $attr->nodeValue;
            }
        }

        return $tagAttributes;
    }

    /**
     * Extract tag content from DOMDocument node
     *
     * @param DOMDocument $dom
     * @param DOMNode     $element
     *
     * @return string
     */
    protected function extractTagContent( DOMDocument $dom, DOMNode $element ): string {
        $childNodes       = $element->hasChildNodes();
        $extractedContent = '';

        if ( !empty( $childNodes ) ) {
            foreach ( $element->childNodes as $node ) {
                $extractedContent .= Emoji::toEntity( Strings::fixNonWellFormedXml( $dom->saveXML( $node ) ) );
            }
        }

        return str_replace( Placeholder::EMPTY_TAG_PLACEHOLDER, '', $extractedContent );
    }

    /**
     * Used to extract <seg-source> and <seg-target>
     *
     * @param DOMDocument $dom
     * @param DOMElement  $childNode
     *
     * @return array
     */
    protected function extractContentWithMarksAndExtTags( DOMDocument $dom, DOMElement $childNode ): array {
        $source = [];

        // example:
        // <g id="1"><mrk mid="0" mtype="seg">An English string with g tags</mrk></g>
        $raw = $this->extractTagContent( $dom, $childNode );

        $markers = preg_split( '#<mrk\s#si', $raw, -1 );

        $mi = 0;
        while ( isset( $markers[ $mi + 1 ] ) ) {
            unset( $mid );

            preg_match( '|mid\s?=\s?["\'](.*?)["\']|si', $markers[ $mi + 1 ], $mid );

            // if it's a Trados file the trailing spaces after </mrk> are meaningful
            // so we add them to
            $trailingSpaces = '';
            if ( $this->xliffProprietary === 'trados' ) {
                preg_match_all( '/<\/mrk>[\s]+/iu', $markers[ $mi + 1 ], $trailingSpacesMatches );

                if ( isset( $trailingSpacesMatches[ 0 ] ) && count( $trailingSpacesMatches[ 0 ] ) > 0 ) {
                    foreach ( $trailingSpacesMatches[ 0 ] as $match ) {
                        $trailingSpaces = str_replace( '</mrk>', '', $match );
                    }
                }
            }

            //re-build the mrk tag after the split
            $originalMark = trim( '<mrk ' . $markers[ $mi + 1 ] );

            $mark_string  = preg_replace( '#^<mrk\s[^>]+>(.*)#', '$1', $originalMark ); // at this point we have: ---> 'Test </mrk> </g>>'
            $mark_content = preg_split( '#</mrk>#si', $mark_string );

            $sourceArray = [
                    'mid'           => ( isset( $mid[ 1 ] ) ) ? $mid[ 1 ] : $mi,
                    'ext-prec-tags' => ( $mi == 0 ? $markers[ 0 ] : "" ),
                    'raw-content'   => ( isset( $mark_content[ 0 ] ) ) ? $mark_content[ 0 ] . $trailingSpaces : '',
                    'ext-succ-tags' => ( isset( $mark_content[ 1 ] ) ) ? $mark_content[ 1 ] : '',
            ];

            $source[] = $sourceArray;

            $mi++;
        }

        return $source;
    }

    /**
     * @param array $originalData
     *
     * @return array
     */
    protected function getDataRefMap( array $originalData ): array {
        // dataRef map
        $dataRefMap = [];
        foreach ( $originalData as $datum ) {
            if ( isset( $datum[ 'attr' ][ 'id' ] ) ) {
                $dataRefMap[ $datum[ 'attr' ][ 'id' ] ] = $datum[ 'raw-content' ];
            }
        }

        return $dataRefMap;
    }

    /**
     * @param $raw
     *
     * @return bool
     */
    protected function stringContainsMarks( $raw ): bool {
        $markers = preg_split( '#<mrk\s#si', $raw, -1 );

        return isset( $markers[ 1 ] );
    }

    /**
     * @param      $noteValue
     * @param bool $escapeStrings
     *
     * @return array
     * @throws Exception
     */
    protected function JSONOrRawContentArray( $noteValue, ?bool $escapeStrings = true ): array {
        //
        // convert double escaped entites
        //
        // Example:
        //
        // &amp;#39; ---> &#39;
        // &amp;amp; ---> &amp;
        // &amp;apos ---> &apos;
        //
        if ( Strings::isADoubleEscapedEntity( $noteValue ) ) {
            $noteValue = Strings::htmlspecialchars_decode( $noteValue, true );
        } else {
            // for non escaped entities $escapeStrings is always true for security reasons
            $escapeStrings = true;
        }

        if ( Strings::isJSON( $noteValue ) ) {
            return [ 'json' => Strings::cleanCDATA( $noteValue ) ];
        }

        return [ 'raw-content' => Strings::fixNonWellFormedXml( $noteValue, $escapeStrings ) ];
    }
}
