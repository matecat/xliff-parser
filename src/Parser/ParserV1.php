<?php

namespace Matecat\XliffParser\Parser;

use Matecat\XliffParser\Exception\DuplicateTransUnitIdInXliff;
use Matecat\XliffParser\Exception\NotFoundIdInTransUnit;

class ParserV1 extends AbstractParser
{
    /**
     * @inheritDoc
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
            /** @var \DOMElement $transUnit */
            foreach ($file->getElementsByTagName('trans-unit') as $transUnit) {

                // metadata
                $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'attr' ] = $this->extractTransUnitMetadata($transUnit, $transUnitIdArrayForUniquenessCheck);

                // notes
                $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'notes' ] = $this->extractTransUnitNotes($transUnit);

                // content

                $j++;
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
     * @param \DOMElement $transUnit
     * @param array       $transUnitIdArrayForUniquenessCheck
     *
     * @return array
     */
    private function extractTransUnitMetadata( \DOMElement $transUnit, array $transUnitIdArrayForUniquenessCheck )
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

        // Approved
        // http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#approved
        if (null !== $transUnit->attributes->getNamedItem('approved')) {
            $metadata[ 'approved' ] = filter_var( $transUnit->attributes->getNamedItem('approved')->nodeValue, FILTER_VALIDATE_BOOLEAN );
        }

        return $metadata;
    }

    /**
     * @param \DOMElement $transUnit
     *
     * @return array
     * @throws \Exception
     */
    private function extractTransUnitNotes( \DOMElement $transUnit )
    {
        $notes = [];
        foreach ($transUnit->getElementsByTagName('note') as $note) {

            $noteValue = trim($note->nodeValue);

            if ('' !== $noteValue) {
                $notes[] = $this->JSONOrRawContentArray($noteValue);
            }
        }

        return $notes;
    }
}
