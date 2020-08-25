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
    public function parse( \DOMDocument $dom )
    {
        // TODO: Implement parse() method.
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
     * @param string $xliffHeadings
     * @param string $fileAttributes
     *
     * @return array
     */
    private function extractMetadata( $xliffHeadings, $fileAttributes)
    {
        $metadata = [];

        // original
        preg_match( '|original\s?=\s?["\'](.*?)["\']|si', $fileAttributes, $temp );
        $metadata[ 'original' ] = (isset( $temp[ 1 ])) ? $temp[ 1 ] : 'no-name';

        // source-language
        unset( $temp );
        preg_match( '|srcLang\s?=\s?["\'](.*?)["\']|si', $xliffHeadings, $temp );
        $metadata[ 'source-language' ] = (isset( $temp[ 1 ])) ? $temp[ 1 ] : 'en-US';

        // datatype
        // @TODO to be implemented

        // target-language
        unset( $temp );
        preg_match( '|trgLang\s?=\s?["\'](.*?)["\']|si', $xliffHeadings, $temp );
        if ( isset( $temp[ 1 ] ) ) {
            $metadata[ 'target-language' ] = $temp[ 1 ];
        }

        // custom MateCat x-attribute
        // @TODO to be implemented

        unset( $temp );

        return $metadata;
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private function getNotes($file)
    {
        preg_match_all( '|<notes.*?>(.+?)</notes>|si', $file, $temp );
        $matches = array_values( $temp[ 1 ] );

        if ( count( $matches ) === 0 ) {
            return [];
        }

        return $matches;
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
     * @param string $file
     *
     * @return array|false|string[]
     */
    private function getTransUnits($file)
    {
        return preg_split( '|<unit[\s>]|si', $file, -1, PREG_SPLIT_NO_EMPTY );
    }

    /**
     * @param string $transUnit
     * @param array $transUnitIdArrayForUniquenessCheck
     *
     * @return array
     */
    private function extractTransUnitMetadata($transUnit, &$transUnitIdArrayForUniquenessCheck)
    {
        $metadata = [];

        // id
        preg_match( '|id\s?=\s?(["\'])(.*?)\1|si', $transUnit, $temp );
        if ( trim( $temp[ 2 ] ) == '' ) {
            throw new NotFoundIdInTransUnit( 'Invalid trans-unit id found. EMPTY value', 400 );
        }

        $transUnitIdArrayForUniquenessCheck[] = trim( $temp[ 2 ] );
        $metadata[ 'id' ] = $temp[ 2 ];

        // translate
        unset( $temp );
        preg_match( '|translate\s?=\s?["\'](.*?)["\']|si', $transUnit, $temp );
        if ( isset( $temp[ 1 ] ) ) {
            $metadata[ 'translate' ] = $temp[ 1 ];
        }

        unset( $temp );

        return $metadata;
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