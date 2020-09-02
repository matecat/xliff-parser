<?php

namespace Matecat\XliffParser\XliffParser;

use Matecat\XliffParser\Exception\DuplicateTransUnitIdInXliff;
use Matecat\XliffParser\Exception\NotFoundIdInTransUnit;

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
                if ($childNode->nodeName === 'group') {
                    foreach ($childNode->childNodes as $nestedChildNode) {
                        if ($nestedChildNode->nodeName === 'unit') {
                            $this->extractTransUnit($nestedChildNode, $transUnitIdArrayForUniquenessCheck, $dom, $output,$i, $j);
                        }
                    }
                } elseif ($childNode->nodeName === 'unit') {
                    $this->extractTransUnit($childNode, $transUnitIdArrayForUniquenessCheck, $dom, $output,$i, $j);
                }
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
    private function extractTransUnit($transUnit, &$transUnitIdArrayForUniquenessCheck, $dom, &$output, &$i, &$j)
    {
        // metadata
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'attr' ] = $this->extractTransUnitMetadata($transUnit, $transUnitIdArrayForUniquenessCheck);

        // notes
        // merge <notes> with key and key-note contained in metadata <mda:metaGroup>
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'notes' ] = $this->extractTransUnitNotes($transUnit);

        // original-data (exclusive for V2)
        // http://docs.oasis-open.org/xliff/xliff-core/v2.0/xliff-core-v2.0.html#originaldata
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'original-data' ] = $this->extractTransUnitOriginalData($transUnit);

        // content
        // in xliff v2 there is not <seg-source> tag, so we need to concatenate all segment <source> and <target>
        // to build the full strings

        $source = [
                'attr' => [],
                'raw-content' => '',
        ];

        $target = [
                'attr' => [],
                'raw-content' => '',
        ];

        /** @var \DOMElement $segment */
        $c = 0;
        foreach ($transUnit->childNodes as $segment) {
            if ($segment->nodeName === 'segment') {
                // loop <segment> to get nested <source> and <target> tag
                foreach ($segment->childNodes as $childNode) {
                    if ($childNode->nodeName === 'source') {
                        $extractedSource = $this->extractContent($dom, $childNode);
                        $source['raw-content'] .= $extractedSource['raw-content'];

                        if(!empty($extractedSource['attr'])){
                            $source['attr'][$c] = $extractedSource['attr'];
                        }
                    }

                    if ($childNode->nodeName === 'target') {
                        $extractedTarget = $this->extractContent($dom, $childNode);
                        $target['raw-content'] .= $extractedTarget['raw-content'];

                        if(!empty($extractedTarget['attr'])){
                            $source['attr'][$c] = $extractedTarget['attr'];
                        }
                    }
                }

                $c++;
            }
        }

        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'source' ] = $source;
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'target' ] = $target;

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
        $transUnitIdArrayForUniquenessCheck[] = $id;
        $metadata[ 'id' ] = $id;

        // translate
        if (null !== $transUnit->attributes->getNamedItem('translate')) {
            $metadata[ 'translate' ] = $transUnit->attributes->getNamedItem('translate')->nodeValue;
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
                    }

                    $dataValue = trim($data->nodeValue);
                    if ('' !== $dataValue) {
                        $originalData[] = array_merge(
                            $this->JSONOrRawContentArray($dataValue),
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

        return $originalData;
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
