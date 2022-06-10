<?php

namespace Matecat\XliffParser\XliffParser;

use Matecat\XliffParser\Exception\DuplicateTransUnitIdInXliff;
use Matecat\XliffParser\Exception\NotFoundIdInTransUnit;
use Matecat\XliffParser\Exception\SegmentIdTooLongException;

class XliffParserV1 extends AbstractXliffParser
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
            $output[ 'files' ][ $i ][ 'attr' ] = $this->extractMetadata($file);

            // reference
            if (!empty($this->extractReference($file))) {
                $output[ 'files' ][ $i ][ 'reference' ] = $this->extractReference($file);
            }

            // trans-units
            $transUnitIdArrayForUniquenessCheck = [];
            $j = 1;
            foreach ($file->childNodes as $body) {
                if ($body->nodeName === 'body') {
                    foreach ($body->childNodes as $childNode) {
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
            }
        }

        return $output;
    }

    /**
     * @param \DOMElement $file
     *
     * @return array
     */
    private function extractMetadata(\DOMElement $file)
    {
        $metadata = [];
        $customAttr = [];

        /** @var \DOMAttr $attribute */
        foreach ($file->attributes as $attribute) {
            switch ($attribute->localName) {
                // original
                case 'original':
                    $metadata[ 'original' ] = $attribute->value;
                    break;

                // source-language
                case 'source-language':
                    $metadata[ 'source-language' ] = $attribute->value;
                    break;

                // data-type
                case 'datatype':
                    $metadata[ 'data-type' ] = $attribute->value;
                    break;

                // target-language
                case 'target-language':
                    $metadata[ 'target-language' ] = $attribute->value;
                    break;
            }

            //Custom MateCat x-Attribute
            preg_match('|x-(.*?)|si', $attribute->localName, $temp);
            if (isset($temp[ 1 ])) {
                $customAttr[ $attribute->localName ] = $attribute->value;
            }
            unset($temp);

            //Custom MateCat namespace Attribute mtc:
            preg_match('|mtc:(.*?)|si', $attribute->localName, $temp);
            if (isset($temp[ 1 ])) {
                $customAttr[ $attribute->localName ] = $attribute->value;
            }
            unset($temp);

            if (!empty($customAttr)) {
                $metadata['custom'] = $customAttr;
            }
        }

        return $metadata;
    }

    /**
     * @param \DOMElement $file
     *
     * @return array
     */
    private function extractReference(\DOMElement $file)
    {
        $reference = [];

        $order = 0;
        foreach ($file->getElementsByTagName('reference') as $ref) {
            /** @var \DOMNode $childNode */
            foreach ($ref->childNodes as $childNode) {
                if ($childNode->nodeName === 'internal-file') {
                    $reference[ $order ][ 'form-type' ] = $childNode->attributes->getNamedItem('form')->nodeValue;
                    $reference[ $order ][ 'base64' ]    = trim($childNode->nodeValue);
                    $order++;
                }
            }
        }

        return $reference;
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
        $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'notes' ] = $this->extractTransUnitNotes($dom, $transUnit);

        // content
        /** @var \DOMElement $childNode */
        foreach ($transUnit->childNodes as $childNode) {
            // source
            if ($childNode->nodeName === 'source') {
                $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'source' ] = $this->extractContent($dom, $childNode);
            }

            // seg-source
            if ($childNode->nodeName === 'seg-source') {
                $rawSegment = $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'source' ]['raw-content'];
                $output[ 'files' ][ $i ][ 'trans-units' ][ $j ]['seg-source'] = $this->extractContentWithMarksAndExtTags($dom, $childNode, $rawSegment);
            }

            // target
            if ($childNode->nodeName === 'target') {
                $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'target' ] = $this->extractContent($dom, $childNode);

                // seg-target
                $targetRawContent = @$output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'target' ][ 'raw-content' ];
                $segSource = @$output[ 'files' ][ $i ][ 'trans-units' ][ $j ]['seg-source'];
                if (isset($targetRawContent) and !empty($targetRawContent) and isset($segSource) and count($segSource) > 0) {
                    $output[ 'files' ][ $i ][ 'trans-units' ][ $j ]['seg-target'] = $this->extractContentWithMarksAndExtTags($dom, $childNode, $targetRawContent);
                }
            }

            // locked
            if ($childNode->nodeName === 'sdl:seg') {
                $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'locked' ] = $this->extractLocked($childNode);
            }
        }

        // context-group
        foreach ($transUnit->getElementsByTagName('context-group') as $contextGroup) {
            $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'context-group' ][] = $this->extractTransUnitContextGroup($dom, $contextGroup);
        }

        // alt-trans
        foreach ($transUnit->getElementsByTagName('alt-trans') as $altTrans) {
            $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'alt-trans' ][] = $this->extractTransUnitAltTrans($altTrans);
        }

        $j++;
    }

    /**
     * @param \DOMElement $transUnit
     * @param array       $transUnitIdArrayForUniquenessCheck
     *
     * @return array
     * @throws \Exception
     */
    private function extractTransUnitMetadata(\DOMElement $transUnit, array &$transUnitIdArrayForUniquenessCheck)
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

        // approved
        // http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#approved
        if (null !== $transUnit->attributes->getNamedItem('approved')) {
            $metadata[ 'approved' ] = filter_var($transUnit->attributes->getNamedItem('approved')->nodeValue, FILTER_VALIDATE_BOOLEAN);
        }

        return $metadata;
    }

    /**
     * @param \DOMElement $transUnit
     *
     * @return array
     * @throws \Exception
     */
    private function extractTransUnitNotes(\DOMDocument $dom, \DOMElement $transUnit)
    {
        $notes = [];
        foreach ($transUnit->getElementsByTagName('note') as $note) {
            $noteValue = $this->extractTagContent($dom, $note);

            if ('' !== $noteValue) {
                $notes[] = $this->JSONOrRawContentArray($noteValue);
            }
        }

        return $notes;
    }

    /**
     * @param \DOMElement $contextGroup
     *
     * @return array
     */
    private function extractTransUnitContextGroup(\DOMDocument $dom, \DOMElement $contextGroup)
    {
        $cg = [];
        $cg['attr'] = $this->extractTagAttributes($contextGroup);

        /** @var \DOMNode $context */
        foreach ($contextGroup->childNodes as $context) {
            if ($context->nodeName === 'context') {
                $cg['contexts'][] = $this->extractContent($dom, $context);
            }
        }

        return $cg;
    }

    /**
     * @param \DOMElement $altTrans
     *
     * @return array
     */
    private function extractTransUnitAltTrans(\DOMElement $altTrans)
    {
        $at = [];
        $at['attr'] = $this->extractTagAttributes($altTrans);

        if ($altTrans->getElementsByTagName('source')->length > 0) {
            $at['source'] = $altTrans->getElementsByTagName('source')->item(0)->nodeValue;
        }

        if ($altTrans->getElementsByTagName('target')) {
            $at['target'] = $altTrans->getElementsByTagName('target')->item(0)->nodeValue;
        }

        return $at;
    }

    /**
     * @param \DOMElement $locked
     *
     * @return bool
     */
    private function extractLocked(\DOMElement $locked)
    {
        return null !== $locked->getAttribute('locked');
    }
}
