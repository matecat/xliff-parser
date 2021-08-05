<?php

namespace Matecat\XliffParser\XliffParser;

use Matecat\XliffParser\Constants\Placeholder;
use Matecat\XliffParser\Exception\DuplicateTransUnitIdInXliff;
use Matecat\XliffParser\Exception\NotFoundIdInTransUnit;
use Matecat\XliffParser\Exception\SegmentIdTooLongException;
use Matecat\XliffParser\Utils\Strings;
use Matecat\XliffParser\XliffUtils\DataRefReplacer;

class XliffParserV2 extends AbstractXliffParser
{
    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function parse(\DOMDocument $dom, $output = [])
    {
        $i = 1;
        /** @var \DOMElement $file */
        foreach ($dom->getElementsByTagName('file') as $file) {

            // metadata
            $output[ 'files' ][ $i ][ 'attr' ] = $this->extractMetadata($dom);

            // notes
            $output[ 'files' ][ $i ]['notes'] = $this->extractNotes($file);

            // trans-units
            $transUnitIdArrayForUniquenessCheck = [];
            $j = 1;
            /** @var \DOMElement $transUnit */
            foreach ($file->childNodes as $childNode) {
                $this->extractTuFromNode($childNode, $transUnitIdArrayForUniquenessCheck, $dom, $output, $i, $j);
            }

            // trans-unit re-count check
            $totalTransUnitsId  = count($transUnitIdArrayForUniquenessCheck);
            $transUnitsUniqueId = count(array_unique($transUnitIdArrayForUniquenessCheck));
            if ($totalTransUnitsId != $transUnitsUniqueId) {
                throw new DuplicateTransUnitIdInXliff("Invalid trans-unit id, duplicate found.", 400);
            }

            $i++;
        }

        return $output;
    }

    /**
     * @param \DOMDocument $dom
     *
     * @return array
     */
    private function extractMetadata(\DOMDocument $dom)
    {
        $metadata = [];

        $xliffNode = $dom->getElementsByTagName('xliff')->item(0);
        $fileNode = $dom->getElementsByTagName('file')->item(0);

        // original
        $metadata[ 'original' ] = (null !== $fileNode->attributes->getNamedItem('original')) ? $fileNode->attributes->getNamedItem('original')->nodeValue : 'no-name';

        // source-language
        $metadata[ 'source-language' ] = (null !== $xliffNode->attributes->getNamedItem('srcLang')) ? $xliffNode->attributes->getNamedItem('srcLang')->nodeValue : 'en-US';

        // datatype
        // @TODO to be implemented

        // target-language
        $metadata[ 'target-language' ] = (null !== $xliffNode->attributes->getNamedItem('trgLang')) ? $xliffNode->attributes->getNamedItem('trgLang')->nodeValue : 'en-US';

        // custom MateCat x-attribute
        // @TODO to be implemented

        return $metadata;
    }

    /**
     * @param \DOMElement $file
     *
     * @return array
     * @throws \Exception
     */
    private function extractNotes(\DOMElement $file)
    {
        $notes = [];

        // loop <notes> to get nested <note> tag
        foreach ($file->childNodes as $childNode) {
            if ($childNode->nodeName === 'notes') {
                foreach ($childNode->childNodes as $note) {
                    $noteValue = trim($note->nodeValue);
                    if ('' !== $noteValue) {
                        $notes[] = $this->JSONOrRawContentArray($noteValue);
                    }
                }
            }
        }

        return $notes;
    }

    /**
     * Extract and populate 'trans-units' array
     *
     * @param $transUnit
     * @param $transUnitIdArrayForUniquenessCheck
     * @param $dom
     * @param $output
     * @param $i
     * @param $j
     *
     * @throws \Exception
     */
    protected function extractTransUnit($transUnit, &$transUnitIdArrayForUniquenessCheck, $dom, &$output, &$i, &$j)
    {
        // metadata
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'attr' ] = $this->extractTransUnitMetadata($transUnit, $transUnitIdArrayForUniquenessCheck);

        // notes
        // merge <notes> with key and key-note contained in metadata <mda:metaGroup>
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'notes' ] = $this->extractTransUnitNotes($transUnit);

        // uuid
        foreach ($output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'notes' ] as $note){
            if(isset($note['raw-content']) and Strings::isAValidUuid($note['raw-content'])){
                $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'attr' ]['uuid'] = $note['raw-content'];
            }
        }

        // original-data (exclusive for V2)
        // http://docs.oasis-open.org/xliff/xliff-core/v2.0/xliff-core-v2.0.html#originaldata
        $originalData = $this->extractTransUnitOriginalData($transUnit);
        if (!empty($originalData)) {
            $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'original-data' ] = $originalData;
            $dataRefMap = $this->getDataRefMap($originalData);
        }

        // additionalTagData (exclusive for V2)
        $additionalTagData = $this->extractTransUnitAdditionalTagData($transUnit);
        if (!empty($additionalTagData)) {
            $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'additional-tag-data' ] = $additionalTagData;
        }

        // content

        $source = [
            'attr' => [],
            'raw-content' => [],
        ];

        $target = [
            'attr' => [],
            'raw-content' => [],
        ];

        $segSource = [];
        $segTarget = [];

        /** @var \DOMElement $segment */
        $c = 0;
        foreach ($transUnit->childNodes as $segment) {
            if ($segment->nodeName === 'segment') {

                // check segment id consistency
                $attr = $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'attr' ];
                $this->checkSegmentIdConsistency($segment, $attr);

                // loop <segment> to get nested <source> and <target> tag
                foreach ($segment->childNodes as $childNode) {
                    if ($childNode->nodeName === 'source') {
                        $extractedSource = $this->extractContent($dom, $childNode);
                        $source['raw-content'][$c] = $extractedSource['raw-content'];

                        if (!empty($originalData)) {
                            $source['replaced-content'][$c] = (new DataRefReplacer($dataRefMap))->replace($source['raw-content'][$c]);
                        }

                        if (!empty($extractedSource['attr'])) {
                            $source['attr'][$c] = $extractedSource['attr'];
                        }

                        // append value to 'seg-source'
                        if ($this->stringContainsMarks($extractedSource['raw-content'])) {
                            $segSource = $this->extractContentWithMarksAndExtTags($dom, $childNode, $extractedSource['raw-content'], $originalData);
                        } else {
                            $segSource[] = [
                                'mid'           => count($segSource) > 0 ? count($segSource) : 0,
                                'ext-prec-tags' => '',
                                'raw-content'   => $extractedSource['raw-content'],
                                'replaced-content'   => (!empty($originalData)) ?  (new DataRefReplacer($dataRefMap))->replace($extractedSource['raw-content']) : null,
                                'ext-succ-tags' => '',
                            ];
                        }
                    }

                    if ($childNode->nodeName === 'target') {
                        $extractedTarget = $this->extractContent($dom, $childNode);
                        $target['raw-content'][$c] = $extractedTarget['raw-content'];

                        if (!empty($originalData)) {
                            $target['replaced-content'][$c] = (new DataRefReplacer($dataRefMap))->replace($target['raw-content'][$c]);
                        }

                        if (!empty($extractedTarget['attr'])) {
                            $target['attr'][$c] = $extractedTarget['attr'];
                        }

                        // append value to 'seg-target'
                        if ($this->stringContainsMarks($extractedTarget['raw-content'])) {
                            $segTarget = $this->extractContentWithMarksAndExtTags($dom, $childNode, $extractedTarget['raw-content'], $originalData);
                        } else {
                            $segTarget[] = [
                                'mid'           => count($segTarget) > 0 ? count($segTarget) : 0,
                                'ext-prec-tags' => '',
                                'raw-content'   => $extractedTarget['raw-content'],
                                'replaced-content' => (!empty($originalData)) ?  (new DataRefReplacer($dataRefMap))->replace($extractedTarget['raw-content']) : null,
                                'ext-succ-tags' => '',
                            ];
                        }
                    }
                }

                $c++;
            }
        }

        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'source' ] = $source;
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'target' ] = $target;
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'seg-source' ] = $segSource;
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'seg-target' ] = $segTarget;

        $j++;
    }

    /**
     * @param \DOMElement $transUnit
     * @param             $transUnitIdArrayForUniquenessCheck
     *
     * @return array
     */
    private function extractTransUnitMetadata(\DOMElement $transUnit, &$transUnitIdArrayForUniquenessCheck)
    {
        $metadata = [];

        // id
        if (null === $transUnit->attributes->getNamedItem('id')) {
            throw new NotFoundIdInTransUnit('Invalid trans-unit id found. EMPTY value', 400);
        }

        $id = $transUnit->attributes->getNamedItem('id')->nodeValue;

        if(strlen($id) > 100){
            throw new SegmentIdTooLongException('Segment-id too long. Max 100 characters allowed', 400);
        }

        $transUnitIdArrayForUniquenessCheck[] = $id;
        $metadata[ 'id' ] = $id;

        // translate
        if (null !== $transUnit->attributes->getNamedItem('translate')) {
            $metadata[ 'translate' ] = $transUnit->attributes->getNamedItem('translate')->nodeValue;
        }

        // tGroupBegin
        if (null !== $transUnit->attributes->getNamedItem('tGroupBegin')) {
            $metadata[ 'tGroupBegin' ] = $transUnit->attributes->getNamedItem('tGroupBegin')->nodeValue;
        }

        // tGroupEnd
        if (null !== $transUnit->attributes->getNamedItem('tGroupEnd')) {
            $metadata[ 'tGroupEnd' ] = $transUnit->attributes->getNamedItem('tGroupEnd')->nodeValue;
        }

        // sizeRestriction
        if (null !== $transUnit->attributes->getNamedItem('sizeRestriction')) {
            $metadata[ 'sizeRestriction' ] = (int)$transUnit->attributes->getNamedItem('sizeRestriction')->nodeValue;
        }

        return $metadata;
    }

    /**
     * @param \DOMElement $transUnit
     *
     * @return array
     * @throws \Exception
     */
    private function extractTransUnitOriginalData(\DOMElement $transUnit)
    {
        $originalData = [];

        // loop <originalData> to get nested content
        foreach ($transUnit->childNodes as $childNode) {
            if ($childNode->nodeName === 'originalData') {
                foreach ($childNode->childNodes as $data) {
                    if (null!== $data->attributes and null !== $data->attributes->getNamedItem('id')) {
                        $dataId = $data->attributes->getNamedItem('id')->nodeValue;

                        $dataValue = str_replace(Placeholder::WHITE_SPACE_PLACEHOLDER, ' ', $data->nodeValue);
                        $dataValue = str_replace(Placeholder::NEW_LINE_PLACEHOLDER,'\n', $dataValue);
                        $dataValue = str_replace(Placeholder::TAB_PLACEHOLDER, '\t', $dataValue);

                        if ('' !== $dataValue) {

                            $jsonOrRawContentArray = $this->JSONOrRawContentArray($dataValue, false);

                            // restore xliff tags
                            if (isset($jsonOrRawContentArray['json'])){
                                $jsonOrRawContentArray['json'] = str_replace([Placeholder::LT_PLACEHOLDER, Placeholder::GT_PLACEHOLDER], ['&lt;','&gt;'], $jsonOrRawContentArray['json']);
                            }

                            if (isset($jsonOrRawContentArray['raw-content'])){
                                $jsonOrRawContentArray['raw-content'] = str_replace([Placeholder::LT_PLACEHOLDER, Placeholder::GT_PLACEHOLDER], ['&lt;','&gt;'], $jsonOrRawContentArray['raw-content']);
                            }

                            $originalData[] = array_merge(
                                $jsonOrRawContentArray,
                                [
                                    'attr' => [
                                        'id' => $dataId
                                    ]
                                ]
                            );
                        }
                    }
                }
            }
        }

        return $originalData;
    }

    /**
     * @param \DOMElement $transUnit
     *
     * @return array
     */
    private function extractTransUnitAdditionalTagData(\DOMElement $transUnit)
    {
        $additionalTagData = [];

        // loop <originalData> to get nested content
        foreach ($transUnit->childNodes as $childNode) {
            if ($childNode->nodeName === 'memsource:additionalTagData') {
                foreach ($childNode->childNodes as $data) {
                    $dataArray = [];

                    // id
                    if ($data->nodeName === 'memsource:tag') {
                        if (null!== $data->attributes and null !== $data->attributes->getNamedItem('id')) {
                            $dataId = $data->attributes->getNamedItem('id')->nodeValue;
                            $dataArray['attr']['id'] = $dataId;
                        }
                    }

                    // content
                    foreach ($data->childNodes as $datum) {
                        if ($datum->nodeName === 'memsource:tagId') {
                            $dataArray['raw-content']['tagId'] = $datum->nodeValue;
                        }

                        if ($datum->nodeName === 'memsource:type') {
                            $dataArray['raw-content']['type'] = $datum->nodeValue;
                        }
                    }

                    if (!empty($dataArray)) {
                        $additionalTagData[] = $dataArray;
                    }
                }
            }
        }

        return $additionalTagData;
    }

    /**
     * Check if segment id is present within tGroupBegin and tGroupEnd attributes
     *
     * @param \DOMElement $segment
     * @param array $attr
     */
    private function checkSegmentIdConsistency(\DOMElement $segment, array $attr)
    {
        if (isset($attr[ 'tGroupBegin' ]) and isset($attr[ 'tGroupEnd' ]) and $segment->attributes->getNamedItem('id')) {
            $id = $segment->attributes->getNamedItem('id')->nodeValue;
            $min = (int)$attr[ 'tGroupBegin' ];
            $max = (int)$attr[ 'tGroupEnd' ];

            if (false === (($min <= $id) and ($id <= $max))) {
                if ($this->logger) {
                    $this->logger->warning('Segment #' . $id . ' is not included within tGroupBegin and tGroupEnd');
                }
            }
        }
    }

    /**
     * @param \DOMElement $transUnit
     *
     * @return array
     * @throws \Exception
     */
    private function extractTransUnitNotes(\DOMElement $transUnit)
    {
        $notes = [];

        // loop <notes> to get nested <note> tag
        foreach ($transUnit->childNodes as $childNode) {
            if ($childNode->nodeName == 'notes') {
                foreach ($childNode->childNodes as $note) {
                    $noteValue = trim($note->nodeValue);
                    if ('' !== $noteValue) {
                        $notes[] = $this->JSONOrRawContentArray($noteValue);
                    }
                }
            }

            if ($childNode->nodeName === 'mda:metadata') {
                foreach ($childNode->childNodes as $metadata) {
                    if ($metadata->nodeName === 'mda:metaGroup') {
                        foreach ($metadata->childNodes as $meta) {
                            if (null!== $meta->attributes and null !== $meta->attributes->getNamedItem('type')) {
                                $type = $meta->attributes->getNamedItem('type')->nodeValue;
                                $metaValue = trim($meta->nodeValue);

                                if ('' !== $metaValue) {
                                    $notes[] = array_merge(
                                        $this->JSONOrRawContentArray($metaValue),
                                        [
                                        'attr' => [
                                                'type' => $type
                                        ]
                                    ]
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        return $notes;
    }
}
