<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffParser;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;
use Matecat\XliffParser\Exception\DuplicateTransUnitIdInXliff;
use Matecat\XliffParser\Exception\NotFoundIdInTransUnit;
use Matecat\XliffParser\Exception\SegmentIdTooLongException;

class XliffParserV1 extends AbstractXliffParser
{
    /**
     * @inheritDoc
     *
     * @param DOMDocument $dom The XLIFF document
     * @param array<string, mixed>|null $output Initial output array
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    public function parse(DOMDocument $dom, ?array $output = []): array
    {
        $i = 1;
        /** @var DOMElement $file */
        foreach ($dom->getElementsByTagName('file') as $file) {
            // metadata
            $output['files'][$i]['attr'] = $this->extractMetadata($file);

            // reference
            // @codeCoverageIgnoreStart
            // Note: internal-file tags are extracted and removed before DOM parsing
            // in XliffParser::removeInternalFileTagFromContent(), so this code is unreachable
            if (!empty($this->extractReference($file))) {
                $output['files'][$i]['reference'] = $this->extractReference($file);
            }
            // @codeCoverageIgnoreEnd

            // trans-units
            $transUnitIdArrayForUniquenessCheck = [];
            $j = 1;
            foreach ($file->childNodes as $body) {
                // external-file
                if ($body->nodeName === 'header') {
                    foreach ($body->childNodes as $header) {
                        $this->extractExternalFile($header, $i, $output);
                    }
                }

                if ($body->nodeName === 'body') {
                    foreach ($body->childNodes as $childNode) {
                        $this->extractTuFromNode(
                            $childNode,
                            $transUnitIdArrayForUniquenessCheck,
                            $dom,
                            $output,
                            $i,
                            $j
                        );
                    }

                    // trans-unit re-count check
                    $totalTransUnitsId = count($transUnitIdArrayForUniquenessCheck);
                    $transUnitsUniqueId = count(array_unique($transUnitIdArrayForUniquenessCheck));
                    if ($totalTransUnitsId != $transUnitsUniqueId) {
                        throw new DuplicateTransUnitIdInXliff("Invalid trans-unit id, duplicate found.", 400);
                    }

                    $i++;
                }
            }
        }

        return $output;
    }

    /**
     * Extract external file info from header node.
     *
     * @param DOMNode $header The header DOM node
     * @param int $i File index
     * @param array<string, mixed> $output Output array being built
     */
    private function extractExternalFile(DOMNode $header, int $i, array &$output): void
    {
        if ($header->nodeName === "skl") {
            foreach ($header->childNodes as $referenceNode) {
                if ($referenceNode->nodeName === "reference") {
                    foreach ($referenceNode->childNodes as $childNode) {
                        if ($childNode->nodeName === "external-file" && $childNode instanceof DOMElement) {
                            $href = $childNode->getAttribute("href");
                            $output['files'][$i]['attr']['external-file'] = $href;
                        }
                    }
                } elseif ($referenceNode->nodeName === "external-file" && $referenceNode instanceof DOMElement) {
                    $href = $referenceNode->getAttribute("href");
                    $output['files'][$i]['attr']['external-file'] = $href;
                }
            }
        } elseif ($header->nodeName === "reference") {
            foreach ($header->childNodes as $referenceNode) {
                if ($referenceNode->nodeName === "external-file" && $referenceNode instanceof DOMElement) {
                    $href = $referenceNode->getAttribute("href");
                    $output['files'][$i]['attr']['external-file'] = $href;
                }
            }
        }
    }

    /**
     * Extract file metadata from DOMElement.
     *
     * @return array<string, mixed>
     */
    private function extractMetadata(DOMElement $file): array
    {
        $metadata = [];
        $customAttr = [];

        /** @var DOMAttr $attribute */
        foreach ($file->attributes as $attribute) {
            switch ($attribute->localName) {
                // original
                case 'original':
                    $metadata['original'] = $attribute->value;
                    break;

                // source-language
                case 'source-language':
                    $metadata['source-language'] = $attribute->value;
                    break;

                // data-type
                case 'datatype':
                    $metadata['data-type'] = $attribute->value;
                    break;

                // target-language
                case 'target-language':
                    $metadata['target-language'] = $attribute->value;
                    break;
                default:
                    break;
            }

            // Custom MateCat x-Attribute
            if (str_starts_with($attribute->localName, 'x-')) {
                $customAttr[$attribute->localName] = $attribute->value;
            }

            // Custom MateCat namespace Attribute mtc:
            if (str_starts_with($attribute->nodeName, 'mtc:')) {
                $customAttr[$attribute->nodeName] = $attribute->value;
            }

            if (!empty($customAttr)) {
                $metadata['custom'] = $customAttr;
            }
        }

        return $metadata;
    }

    /**
     * Extract reference data from file element.
     *
     * @return array<int, array{form-type: string, base64: string}>
     */
    private function extractReference(DOMElement $file): array
    {
        $reference = [];

        $order = 0;
        foreach ($file->getElementsByTagName('reference') as $ref) {
            /** @var DOMNode $childNode */
            foreach ($ref->childNodes as $childNode) {
                // @codeCoverageIgnoreStart
                // Note: internal-file tags are extracted and removed before DOM parsing
                // in XliffParser::removeInternalFileTagFromContent(), so this code is unreachable
                if ($childNode->nodeName === 'internal-file') {
                    $reference[$order]['form-type'] = $childNode->attributes->getNamedItem('form')->nodeValue;
                    $reference[$order]['base64'] = trim($childNode->nodeValue);
                    $order++;
                }
                // @codeCoverageIgnoreEnd
            }
        }

        return $reference;
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
     *
     * @throws Exception
     */
    protected function extractTransUnit(
        DOMElement $transUnit,
        array &$transUnitIdArrayForUniquenessCheck,
        DOMDocument $dom,
        array &$output,
        int &$i,
        int &$j,
        ?array $contextGroups = []
    ): void {
        // metadata
        $output['files'][$i]['trans-units'][$j]['attr'] = $this->extractTransUnitMetadata(
            $transUnit,
            $transUnitIdArrayForUniquenessCheck
        );

        // notes
        $output['files'][$i]['trans-units'][$j]['notes'] = $this->extractTransUnitNotes($dom, $transUnit);

        // content
        /** @var DOMElement $childNode */
        foreach ($transUnit->childNodes as $childNode) {
            // source
            if ($childNode->nodeName === 'source') {
                $output['files'][$i]['trans-units'][$j]['source'] = $this->extractContent($dom, $childNode);
            }

            // seg-source
            if ($childNode->nodeName === 'seg-source') {
                $output['files'][$i]['trans-units'][$j]['seg-source'] = $this->extractContentWithMarksAndExtTags(
                    $dom,
                    $childNode
                );
            }

            // target
            if ($childNode->nodeName === 'target') {
                $output['files'][$i]['trans-units'][$j]['target'] = $this->extractContent($dom, $childNode);

                // seg-target
                $targetRawContent = $output['files'][$i]['trans-units'][$j]['target']['raw-content'];
                $segSource = $output['files'][$i]['trans-units'][$j]['seg-source'] ?? null;

                if (!empty($targetRawContent) && isset($segSource) && count($segSource) > 0) {
                    $output['files'][$i]['trans-units'][$j]['seg-target'] = $this->extractContentWithMarksAndExtTags(
                        $dom,
                        $childNode
                    );
                    $output['files'][$i]['trans-units'][$j]['seg-target'][0]['attr'] = $this->extractTagAttributes(
                        $childNode
                    );
                }
            }

            // locked
            if ($childNode->nodeName === 'sdl:seg-defs') {
                $this->extractLocked(
                    $childNode,
                    $output['files'][$i]['trans-units'][$j]['seg-source']
                );
            }
        }

        // context-group
        // Note: Context groups can exist at multiple levels in XLIFF v1 and need to be collected from both sources:
        // 1. First loop: Process context groups inherited from parent elements (e.g., <group> tags)
        //    These are passed via the $contextGroups parameter and apply to all child trans-units
        // 2. Second loop: Process context groups directly inside the current <trans-unit> element
        //    These are specific to this particular trans-unit
        // Both are merged into the same output array to provide complete context information for the translation unit
        if (!empty($contextGroups)) {
            foreach ($contextGroups as $contextGroup) {
                $output['files'][$i]['trans-units'][$j]['context-group'][] = $this->extractTransUnitContextGroup(
                    $dom,
                    $contextGroup
                );
            }
        }

        // 2. Second loop: Process context groups directly inside the current <trans-unit> element
        //    These are specific to this particular trans-unit
        foreach ($transUnit->getElementsByTagName('context-group') as $contextGroup) {
            $output['files'][$i]['trans-units'][$j]['context-group'][] = $this->extractTransUnitContextGroup(
                $dom,
                $contextGroup
            );
        }

        // alt-trans
        foreach ($transUnit->getElementsByTagName('alt-trans') as $altTrans) {
            $output['files'][$i]['trans-units'][$j]['alt-trans'][] = $this->extractTransUnitAltTrans($altTrans);
        }

        $j++;
    }

    /**
     * Extract trans-unit metadata from DOMElement.
     *
     * @param DOMElement $transUnit The trans-unit element
     * @param array<int, string> $transUnitIdArrayForUniquenessCheck Array to track trans-unit IDs
     *
     * @param-out array<int, string> $transUnitIdArrayForUniquenessCheck
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    private function extractTransUnitMetadata(DOMElement $transUnit, array &$transUnitIdArrayForUniquenessCheck): array
    {
        $metadata = [];

        // id MUST NOT be null
        if (null === $transUnit->attributes->getNamedItem('id')) {
            throw new NotFoundIdInTransUnit('Invalid trans-unit id found. EMPTY value', 400);
        }

        /**
         * @var DOMAttr $element
         */
        foreach ($transUnit->attributes as $element) {
            if ($element->nodeName === "id") {
                $id = $element->nodeValue ?? '';

                if (strlen($id) > 100) {
                    throw new SegmentIdTooLongException('Segment-id too long. Max 100 characters allowed', 400);
                }

                $transUnitIdArrayForUniquenessCheck[] = $id;
                $metadata['id'] = $id;
            } elseif ($element->nodeName === "approved") {
                // approved as BOOLEAN
                // http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#approved
                $metadata[$element->nodeName] = filter_var($element->nodeValue, FILTER_VALIDATE_BOOLEAN);
            } elseif ($element->nodeName === "maxwidth") {
                // we ignore ( but we get ) the attribute size-unit="char" assuming that a restriction is everytime done by character
                // we duplicate the info to allow Xliff V1 and V2 to work the same
                $metadata['sizeRestriction'] = filter_var($element->nodeValue, FILTER_SANITIZE_NUMBER_INT);
                $metadata[$element->nodeName] = filter_var($element->nodeValue, FILTER_SANITIZE_NUMBER_INT);
            } else {
                $metadata[$element->nodeName] = $element->nodeValue;
            }
        }

        return $metadata;
    }

    /**
     * Extract notes from trans-unit.
     *
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    private function extractTransUnitNotes(DOMDocument $dom, DOMElement $transUnit): array
    {
        $notes = [];
        foreach ($transUnit->getElementsByTagName('note') as $note) {
            $noteValue = $this->extractTagContent($dom, $note);

            if ('' !== $noteValue) {
                $extractedNote = $this->parseNoteStringIntoArray($noteValue);

                // extract all the attributes
                foreach ($note->attributes as $attribute) {
                    $extractedNote[$attribute->name] = $attribute->value;
                }

                $notes[] = $extractedNote;
            }
        }

        return $notes;
    }

    /**
     * Extract context group data.
     *
     * @return array<string, mixed>
     */
    private function extractTransUnitContextGroup(DOMDocument $dom, DOMElement $contextGroup): array
    {
        $cg = [];
        $cg['attr'] = $this->extractTagAttributes($contextGroup);

        /** @var DOMNode $context */
        foreach ($contextGroup->childNodes as $context) {
            if ($context->nodeName === 'context') {
                $cg['contexts'][] = $this->extractContent($dom, $context);
            }
        }

        return $cg;
    }

    /**
     * Extract alternative translation data.
     *
     * @return array<string, mixed>
     */
    private function extractTransUnitAltTrans(DOMElement $altTrans): array
    {
        $at = [];
        $at['attr'] = $this->extractTagAttributes($altTrans);

        if ($altTrans->getElementsByTagName('source')->length > 0) {
            $at['source'] = $altTrans->getElementsByTagName('source')->item(0)->nodeValue;
        }

        if ($altTrans->getElementsByTagName('target')->length > 0) {
            $at['target'] = $altTrans->getElementsByTagName('target')->item(0)->nodeValue;
        }

        return $at;
    }

    /**
     * Set the locked status on marks based on sdl:seg-defs.
     *
     * @param DOMElement $sdl_seg_defs The sdl:seg-defs element
     * @param array<int, array<string, mixed>> $marks Array of marks indexed numerically with 'mid' property
     */
    private function extractLocked(DOMElement $sdl_seg_defs, array &$marks): void
    {
        // Build a map of sdl:seg id => locked status
        $lockedMap = [];
        /** @var DOMElement $sdl_seg */
        foreach ($sdl_seg_defs->childNodes as $sdl_seg) {
            if ($sdl_seg->nodeName === 'sdl:seg') {
                $lockedMap[$sdl_seg->getAttribute('id')] =
                    $sdl_seg->hasAttribute('locked') &&
                    $sdl_seg->getAttribute('locked') === 'true';
            }
        }

        // Match the mrk by 'mid' value and set locked status
        foreach ($marks as $index => $mark) {
            $mid = $mark['mid'] ?? null;
            if ($mid !== null && isset($lockedMap[$mid])) {
                $marks[$index]['locked'] = $lockedMap[$mid];
            }
        }
    }
}
