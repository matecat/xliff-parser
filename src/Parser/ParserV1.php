<?php

namespace Matecat\XliffParser\Parser;

class ParserV1 extends AbstractParser
{
    /**
     * @inheritDoc
     */
    public function parse(\DOMDocument $dom, $output = [])
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
//
//        foreach ( $this->getFiles($xliffContent) as $i => $file ) {
//            if($i > 0){
//                $fileAttributes = $this->getFileAttributes($file);
//
//                // metadata
//                $xliff[ 'files' ][ $i ][ 'attr' ] = $this->extractMetadata($fileAttributes);
//
//                // reference
//
//                // trans-unit
//                foreach ( $this->getTransUnits($file) as $j => $transUnit ) {
//                    if($j > 0){
//                        $xliff[ 'files' ][ $i ][ 'trans-units' ][ $j ][ 'attr' ] = $this->extractTransUnitMetadata($transUnit);
//                    }
//                }
//            }
//        }
//
//        return $xliff;
//    }

    /**
     * @param string $fileAttributes
     *
     * @return array
     */
    private function extractMetadata( $fileAttributes)
    {
        $metadata = [];

        // original
        preg_match( '|original\s?=\s?["\'](.*?)["\']|si', $fileAttributes, $temp );
        $metadata[ 'original' ] = (isset( $temp[ 1 ])) ? $temp[ 1 ] : 'no-name';

        // source-language
        unset( $temp );
        preg_match( '|source-language\s?=\s?["\'](.*?)["\']|si', $fileAttributes, $temp );
        $metadata[ 'source-language' ] = (isset( $temp[ 1 ])) ? $temp[ 1 ] : 'en-US';

        // datatype
        unset( $temp );
        preg_match( '|datatype\s?=\s?["\'](.*?)["\']|si', $fileAttributes, $temp );
        $metadata[ 'datatype' ] = (isset( $temp[ 1 ])) ? $temp[ 1 ] : 'txt';

        // target-language
        unset( $temp );
        preg_match( '|target-language\s?=\s?["\'](.*?)["\']|si', $fileAttributes, $temp );
        if ( isset( $temp[ 1 ] ) ) {
            $metadata[ 'target-language' ] = $temp[ 1 ];
        }

        // custom MateCat x-attribute
        unset( $temp );
        preg_match( '|x-(.*?)=\s?["\'](.*?)["\']|si', $fileAttributes, $temp );
        if ( isset( $temp[ 1 ] ) ) {
            $metadata[ 'custom' ][ $temp[ 1 ] ] = $temp[ 2 ];
        }

        unset( $temp );

        return $metadata;
    }

    /**
     * @param string $file
     *
     * @return array|false|string[]
     */
    private function getTransUnits($file)
    {
        return preg_split( '|<trans-unit[\s>]|si', $file, -1, PREG_SPLIT_NO_EMPTY );
    }

    private function extractTransUnitMetadata($transUnit)
    {

    }
}