<?php

namespace Matecat\XliffParser\Parser;

use Matecat\XliffParser\Exception\DuplicateTransUnitIdInXliff;
use Matecat\XliffParser\Exception\NotFoundIdInTransUnit;
use Matecat\XliffParser\Utils\Strings;

class ParserV2 extends AbstractParser
{
    /**
     * @inheritDoc
     */
    public function parse( \DOMDocument $dom, $output = [] )
    {
        $i = 1;
        /** @var \DOMElement $file */
        foreach( $dom->getElementsByTagName('file') as $file )
        {
            // metadata
            $output[ 'files' ][ $i ][ 'attr' ] = $this->extractMetadata($dom);

            // notes
            $output[ 'files' ][ $i ]['notes'] = $this->extractNotes($file);

            // trans-units
            $transUnitIdArrayForUniquenessCheck = [];
            $j = 1;
            /** @var \DOMElement $transUnit */
            foreach ($dom->getElementsByTagName('unit') as $transUnit){

                // metadata
                $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'attr' ] = $this->extractTransUnitMetadata($transUnit, $transUnitIdArrayForUniquenessCheck);

                // notes
                // merge <notes> with key-note contained in metadata <mda:metaGroup>

                // original-data (exclusive for V2)
                // http://docs.oasis-open.org/xliff/xliff-core/v2.0/xliff-core-v2.0.html#originaldata
                $output[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'original-data' ] = $this->extractTransUnitOriginalData($transUnit);

                $j++;
            }

            $i++;
        }

        return $output;
    }

//    /**
//     * @param string $xliffContent
//     *
//     * @return array
//     */
//    public function parse( $xliffContent )
//    {
//        $xliff = [];
//        $xliffContent = $this->forceUft8Encoding($xliffContent, $xliff);
//        $xliffHeadings = $this->getFiles($xliffContent)[0];
//
//        foreach ( $this->getFiles($xliffContent) as $i => $file ) {
//            if($i > 0){
//                // get <file> attributes
//                $fileAttributes = $this->getFileAttributes($file);
//
//                // metadata
//                $xliff[ 'files' ][ $i ][ 'attr' ] = $this->extractMetadata($xliffHeadings, $fileAttributes);
//
//                // notes
//                foreach ( Strings::extractTag('notes', $file) as $n => $note ) {
//                    if ( $n === 0 ) {
//                        $xliff[ 'files' ][ $i ]['notes'] = $this->extractNote($note);
//                    }
//                }
//
//                // trans-units
//                $transUnitIdArrayForUniquenessCheck = [];
//
//                foreach ( $this->getTransUnits($file) as $j => $transUnit ) {
//                    if($j > 0){
//                        // metadata
//                        $xliff[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'attr' ] = $this->extractTransUnitMetadata($transUnit, $transUnitIdArrayForUniquenessCheck);
//
//                        // notes
//                        // merge <notes> with key-note contained in metadata <mda:metaGroup>
//                        $transUnitNotes = [];
//                        foreach ( $this->getTransUnitNotes($transUnit) as $note ) {
//                            $transUnitNotes = array_merge($transUnitNotes, $this->extractAndAggregateTransUnitNotes($note));
//                        }
//                        $xliff[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'notes' ] = $transUnitNotes;
//
//                        // original-data (exclusive for V2)
//                        // http://docs.oasis-open.org/xliff/xliff-core/v2.0/xliff-core-v2.0.html#originaldata
//                        if($this->extractOriginalData($transUnit)) {
//                            $xliff[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'original-data' ] = $this->extractOriginalData($transUnit);
//                        }
//                    }
//                }
//
//                // trans-unit re-count check
//                $totalTransUnitsId  = count( $transUnitIdArrayForUniquenessCheck );
//                $transUnitsUniqueId = count( array_unique( $transUnitIdArrayForUniquenessCheck ) );
//                if ( $totalTransUnitsId != $transUnitsUniqueId ) {
//                    throw new DuplicateTransUnitIdInXliff( "Invalid trans-unit id, duplicate found.", 400 );
//                }
//            }
//        }
//
//        return $xliff;
//    }

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
    private function extractNotes( \DOMElement $file )
    {
        $notes = [];

        // loop <notes> to get nested <note> tag
        foreach ( $file->childNodes as $childNode ) {
            if ( $childNode->nodeName == 'notes' ) {
                foreach ( $childNode->childNodes as $note ) {
                    $noteValue = trim($note->nodeValue);
                    if('' !== $noteValue){
                        if ( Strings::isJSON( $noteValue ) ) {
                            $notes[]['json'] = Strings::cleanCDATA( $noteValue );
                        } else {
                            $notes[]['raw-content'] = Strings::fixNonWellFormedXml( $noteValue );
                        }
                    }
                }
            }
        }

        return $notes;
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
        if ( null === $transUnit->attributes->getNamedItem('id') ) {
            throw new NotFoundIdInTransUnit( 'Invalid trans-unit id found. EMPTY value', 400 );
        }

        $id = $transUnit->attributes->getNamedItem('id')->nodeValue;
        $transUnitIdArrayForUniquenessCheck[] = $id;
        $metadata[ 'id' ] = $id;

        // translate
        if ( null !== $transUnit->attributes->getNamedItem('translate') ) {
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
        foreach ( $transUnit->childNodes as $childNode ) {
            if ( $childNode->nodeName == 'originalData' ) {
                foreach ( $childNode->childNodes as $data ) {

                    if(null!== $data->attributes and null !== $data->attributes->getNamedItem('id')){
                        $dataId = $data->attributes->getNamedItem('id')->nodeValue;
                    }

                    $dataValue = trim($data->nodeValue);
                    if('' !== $dataValue){
                        if ( Strings::isJSON( $dataValue ) ) {
                            $originalData[] = [
                              'json' => Strings::cleanCDATA( $dataValue ),
                              'attr' => [
                                  'id' => $dataId
                              ]
                            ];
                        } else {
                            $originalData[] = [
                                'raw-content' => Strings::fixNonWellFormedXml( $dataValue ),
                                'attr' => [
                                    'id' => $dataId
                                ]
                            ];
                        }
                    }
                }
            }
        }

        return $originalData;
    }





















    /**
     * @param $note
     *
     * @return array
     * @throws \Exception
     */
    private function extractNote($note)
    {
        $notes = [];
        $matches = Strings::extractTag('note', $note);

        foreach ($matches as $match){
            if ( Strings::isJSON( $match ) ) {
                $notes[]['json'] = Strings::cleanCDATA( $match );
            } else {
                $notes[]['raw-content'] = Strings::fixNonWellFormedXml( $match );
            }
        }

        return $notes;
    }

    /**
     * @param string $note
     *
     * @return array
     */
    private function extractNoteFromMetaGroup($note)
    {
        $notes = [];

        preg_match_all( '|<mda:metaGroup.*?>(.+?)</mda:metaGroup>|si', $note, $temp );
        $matches = array_values( $temp[ 1 ] );

        if ( count( $matches ) === 0 ) {
            return [];
        }

        foreach ($matches as $nn => $meta){
            preg_match_all( '|<mda:meta type="key">(.+?)</mda:meta>|si', $meta, $tmpK );
            $metaMatchesKey = array_values( $tmpK[ 1 ] );

            if(count($metaMatchesKey) > 0){
                $notes[$nn]['key'] = $metaMatchesKey[0];
            }

            unset($tmpK);

            preg_match_all( '|<mda:meta type="key-note">(.+?)</mda:meta>|si', $meta, $tmpK );
            $metaMatchesKey = array_values( $tmpK[ 1 ] );

            if(count($metaMatchesKey) > 0){
                $notes[$nn]['key-note'] = trim($metaMatchesKey[0]);
            }

            unset($tmpK);
        }

        return $notes;
    }





    /**
     * @param string $transUnit
     *
     * @return array
     */
    private function getTransUnitNotes($transUnit)
    {
        $temp = null;

        preg_match_all( '|<notes.*?>(.+?)</notes>|si', $transUnit, $temp );
        $notesMatches = array_values( $temp[ 1 ] );

        unset($temp);

        preg_match_all( '|<mda:metadata.*?>(.+?)</mda:metadata>|si', $transUnit, $temp );
        $metadataMatches = array_values( $temp[ 1 ] );

        unset($temp);

        return array_merge($notesMatches, $metadataMatches);
    }

    /**
     * @param string $note
     *
     * @return array
     */
    private function extractAndAggregateTransUnitNotes($note)
    {
        if (strpos($note, '<note') !== false) {
            return $this->extractNote($note);
        }

        if (strpos($note, '<mda:metaGroup') !== false) {
            return $this->extractNoteFromMetaGroup($note);
        }
    }

    /**
     * @param string $transUnit
     *
     * @return array|null
     */
    private function extractOriginalData($transUnit)
    {
        $originalData = [];
        $temp = null;

        preg_match_all( '|<originalData.*?>(.+?)</originalData>|si', $transUnit, $temp );
        $matches = array_values( $temp[ 1 ] );

        if ( count( $matches ) === 0 ) {
            return null;
        }

        foreach ( $matches as $match ) {
            $originalData[] = $match;
        }

        unset( $temp );

        return $originalData;
    }
}