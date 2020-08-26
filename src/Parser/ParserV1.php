<?php

namespace Matecat\XliffParser\Parser;

class ParserV1 extends AbstractParser
{
    /**
     * @inheritDoc
     */
    public function parse(\DOMDocument $dom, $output = [])
    {
        $i = 0;
        /** @var \DOMElement $file */
        foreach ($dom->getElementsByTagName('file') as $file) {

            // First element in the XLIFF split is the content before <file> (header), skipping
            if ($i > 0) {
                // metadata
                $output[ 'files' ][ $i ][ 'attr' ] = $this->extractMetadata($file);

                // reference
                if (!empty($this->extractReference($file))) {
                    $output[ 'files' ][ $i ][ 'reference' ] = $this->extractReference($file);
                }
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
}
