<?php

declare(strict_types=1);

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

abstract class AbstractXliffParser
{

    protected const int MAX_GROUP_RECURSION_LEVEL = 50;

    public function __construct(
        protected readonly int $xliffVersion,
        protected readonly ?string $xliffProprietary = null,
        protected readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * @return string
     */
    protected function getTuTagName(): string
    {
        return ($this->xliffVersion === 1) ? 'trans-unit' : 'unit';
    }

    /**
     * Parse XLIFF document and extract translation data.
     *
     * @param DOMDocument $dom The XLIFF document
     * @param array<string, mixed>|null $output Initial output array
     *
     * @return array<string, mixed> Parsed XLIFF data
     */
    abstract public function parse(DOMDocument $dom, ?array $output = []): array;

    /**
     * Extract trans-unit content from the current node.
     *
     * @param DOMNode $childNode The current DOM node
     * @param array<int, string> $transUnitIdArrayForUniquenessCheck Array to track trans-unit IDs for uniqueness
     * @param DOMDocument $dom The DOM document
     * @param array<string, mixed> $output Output array being built
     * @param int $i File index
     * @param int $j Trans-unit index
     * @param array<int, DOMElement>|null $contextGroups Context groups from parent elements
     * @param int|null $recursionLevel Current recursion depth for nested groups
     */
    protected function extractTuFromNode(
        DOMNode $childNode,
        array &$transUnitIdArrayForUniquenessCheck,
        DOMDocument $dom,
        array &$output,
        int &$i,
        int &$j,
        ?array $contextGroups = [],
        ?int $recursionLevel = 0
    ): void {
        if ($childNode->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        if ($childNode->nodeName === 'group') {
            // add nested context-groups
            foreach ($childNode->childNodes as $nestedChildNode) {
                if ($nestedChildNode->nodeName === 'context-group' && $nestedChildNode instanceof DOMElement) {
                    $contextGroups[] = $nestedChildNode;
                }
            }

            // avoid infinite recursion
            $recursionLevel++;

            foreach ($childNode->childNodes as $nestedChildNode) {
                // nested groups
                if ($nestedChildNode->nodeName === 'group') {
                    if ($recursionLevel < self::MAX_GROUP_RECURSION_LEVEL) {
                        $this->extractTuFromNode(
                            $nestedChildNode,
                            $transUnitIdArrayForUniquenessCheck,
                            $dom,
                            $output,
                            $i,
                            $j,
                            $contextGroups,
                            $recursionLevel
                        );
                    } else {
                        throw new OverflowException(
                            "Maximum tag group nesting level of '" . self::MAX_GROUP_RECURSION_LEVEL . "' reached, aborting!"
                        );
                    }
                } elseif (
                    $nestedChildNode->nodeName === $this->getTuTagName() &&
                    $nestedChildNode instanceof DOMElement
                ) {
                    $this->extractTransUnit(
                        $nestedChildNode,
                        $transUnitIdArrayForUniquenessCheck,
                        $dom,
                        $output,
                        $i,
                        $j,
                        $contextGroups
                    );
                }
            }
        } elseif ($childNode->nodeName === $this->getTuTagName() && $childNode instanceof DOMElement) {
            $this->extractTransUnit(
                $childNode,
                $transUnitIdArrayForUniquenessCheck,
                $dom,
                $output,
                $i,
                $j,
                $contextGroups
            );
        }
    }

    /**
     * Extract and populate 'trans-units' array.
     *
     * @param DOMElement $transUnit The trans-unit element
     * @param array<int, string> $transUnitIdArrayForUniquenessCheck Array to track trans-unit IDs
     * @param DOMDocument $dom The DOM document
     * @param array<string, mixed> $output Output array being built
     * @param int $i File index
     * @param int $j Trans-unit index
     * @param array<int, DOMElement>|null $contextGroups Context groups from parent elements
     */
    abstract protected function extractTransUnit(
        DOMElement $transUnit,
        array &$transUnitIdArrayForUniquenessCheck,
        DOMDocument $dom,
        array &$output,
        int &$i,
        int &$j,
        ?array $contextGroups = []
    ): void;

    /**
     * Extract tag content and attributes from a DOM node.
     *
     * @return array{raw-content: string, attr: array<string, string>}
     */
    protected function extractContent(DOMDocument $dom, DOMNode $node): array
    {
        return [
            'raw-content' => $this->extractTagContent($dom, $node),
            'attr' => $this->extractTagAttributes($node)
        ];
    }

    /**
     * Extract attributes from a DOM element if they are present.
     *
     * Example:
     * <p align=center style="font-size: 12px;">some text</p>
     *
     * $attr->nodeName == 'align' :: $attr->nodeValue == 'center'
     * $attr->nodeName == 'style' :: $attr->nodeValue == 'font-size: 12px;'
     *
     * @return array<string, string>
     */
    protected function extractTagAttributes(DOMNode $element): array
    {
        $tagAttributes = [];

        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attr) {
                $tagAttributes[$attr->nodeName] = $attr->nodeValue;
            }
        }

        return $tagAttributes;
    }

    /**
     * Extract tag content from DOMDocument node.
     */
    protected function extractTagContent(DOMDocument $dom, DOMNode $element): string
    {
        $childNodes = $element->hasChildNodes();
        $extractedContent = '';

        if (!empty($childNodes)) {
            foreach ($element->childNodes as $node) {
                $savedXml = $dom->saveXML($node);
                if ($savedXml !== false) {
                    $extractedContent .= Emoji::toEntity(Strings::fixNonWellFormedXml($savedXml));
                }
            }
        }

        return str_replace(Placeholder::EMPTY_TAG_PLACEHOLDER, '', $extractedContent);
    }

    /**
     * Used to extract <seg-source> and <seg-target>.
     *
     * @return array<int, array{mid: string|int, ext-prec-tags: string, raw-content: string, ext-succ-tags: string}>
     */
    protected function extractContentWithMarksAndExtTags(DOMDocument $dom, DOMElement $childNode): array
    {
        $source = [];

        // example:
        // <g id="1"><mrk mid="0" mtype="seg">An English string with g tags</mrk></g>
        $raw = $this->extractTagContent($dom, $childNode);

        $markers = preg_split('#<mrk\s#i', $raw, -1);

        $mi = 0;
        while (isset($markers[$mi + 1])) {
            unset($mid);

            preg_match('|mid\s?=\s?["\'](.*?)["\']|si', $markers[$mi + 1], $mid);

            // if it's a Trados file the trailing spaces after </mrk> are meaningful
            // so we add them to
            $trailingSpaces = '';
            if ($this->xliffProprietary === 'trados') {
                preg_match_all('/<\/mrk>\s+/iu', $markers[$mi + 1], $trailingSpacesMatches);

                if (count($trailingSpacesMatches[0]) > 0) {
                    foreach ($trailingSpacesMatches[0] as $match) {
                        $trailingSpaces = str_replace('</mrk>', '', $match);
                    }
                }
            }

            //re-build the mrk tag after the split
            $originalMark = trim('<mrk ' . $markers[$mi + 1]);

            $mark_string = preg_replace(
                '#^<mrk\s[^>]+>(.*)#',
                '$1',
                $originalMark
            ); // at this point we have: ---> 'Test </mrk> </g>>'
            $mark_content = preg_split('#</mrk>#i', $mark_string);

            $sourceArray = [
                'mid' => (isset($mid[1])) ? $mid[1] : $mi,
                'ext-prec-tags' => ($mi == 0 ? $markers[0] : ""),
                'raw-content' => (isset($mark_content[0])) ? $mark_content[0] . $trailingSpaces : '',
                'ext-succ-tags' => (isset($mark_content[1])) ? $mark_content[1] : '',
            ];

            $source[] = $sourceArray;

            $mi++;
        }

        return $source;
    }

    /**
     * Check if a string contains mrk tags.
     */
    protected function stringContainsMarks(string $raw): bool
    {
        $markers = preg_split('#<mrk\s#i', $raw, -1);

        return isset($markers[1]);
    }

    /**
     * Parse note value and return array with JSON or raw content.
     *
     * @return array{json?: string, raw-content?: string}
     * @throws Exception
     */
    protected function JSONOrRawContentArray(string $noteValue, ?bool $escapeStrings = true): array
    {
        //
        // convert double escaped entites
        //
        // Example:
        //
        // &amp;#39; ---> &#39;
        // &amp;amp; ---> &amp;
        // &amp;apos ---> &apos;
        //
        if (Strings::isADoubleEscapedEntity($noteValue)) {
            $noteValue = Strings::htmlSpecialCharsDecode($noteValue, true);
        } else {
            // for non escaped entities $escapeStrings is always true for security reasons
            $escapeStrings = true;
        }

        if (Strings::isJSON($noteValue)) {
            return ['json' => Strings::cleanCDATA($noteValue)];
        }

        return ['raw-content' => Strings::fixNonWellFormedXml($noteValue, $escapeStrings)];
    }
}
