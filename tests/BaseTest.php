<?php

namespace Matecat\XliffParser\Tests;

use CURLFile;
use DOMDocument;
use Exception;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\XliffParser;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use Matecat\XmlParser\XmlDomLoader;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase {
    /**
     * @param string $file
     *
     * @return false|string
     */
    protected function getTestFile( $file ) {
        return file_get_contents( __DIR__ . '/files/' . $file );
    }

    /**
     * @param $file
     *
     * @return DOMDocument
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    protected function getTestFileAsDOMElement( $file ) {
        return XmlDomLoader::load( file_get_contents( __DIR__ . '/files/' . $file ) );
    }

    /**
     * @param string $file
     * @param array  $expected
     *
     * @throws InvalidXmlException
     * @throws XmlParsingException
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     */
    protected function assertXliffEquals( $file, array $expected = [] ) {
        $parser = new XliffParser();

        $this->assertEquals( $expected, $parser->xliffToArray( $this->getTestFile( $file ) ) );
    }

    /**
     * Asserts that two associative arrays are similar.
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * @param array $expected
     * @param array $array
     */
    protected function assertArraySimilar( array $expected, array $array ) {
        $this->assertTrue( count( array_diff_key( $array, $expected ) ) === 0 );

        foreach ( $expected as $key => $value ) {
            if ( is_array( $value ) ) {
                $this->assertArraySimilar( $value, $array[ $key ] );
            } else {
                $this->assertContains( trim( $value ), trim( $array[ $key ] ) );
            }
        }
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function getTransUnitsForReplacementTest( $data ) {
        $transUnits = [];

        foreach ( $data as $i => $k ) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            $internalId = $k[ 'internal_id' ];

            $transUnits[ $internalId ] [] = $i;

            $data[ 'matecat|' . $internalId ] [] = $i;
        }

        return $transUnits;
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function getData( $data ) {
        $transUnits = [];

        foreach ( $data as $i => $k ) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            $internalId = $k[ 'internal_id' ];

            $transUnits[ $internalId ] [] = $i;

            $data[ 'matecat|' . $internalId ] [] = $i;
        }

        return [
                'data'       => $data,
                'transUnits' => $transUnits,
        ];
    }

    /**
     * @param string $url
     * @param array  $data
     *
     * @return bool|string
     */
    protected function httpPost( $url, $data, $headers ) {

        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        $body    = curl_exec( $ch );
        $error   = curl_error( $ch );
        $errorNo = curl_errno( $ch );
        $info    = curl_getinfo( $ch );

        curl_close( $ch );

        $http          = new \stdClass();
        $http->body    = $body;
        $http->error   = $error;
        $http->errorNo = $errorNo;
        $http->info    = $info;

        return $http;
    }

    /**
     * @param $xliff20
     *
     * @return array
     * @throws Exception
     */
    protected function validateXliff20( $xliff20 ) {

        $sessionCurl = curl_init( "https://dev.maxprograms.com/Validation/version" );
        curl_setopt( $sessionCurl, CURLOPT_RETURNTRANSFER, true );
        $sessionValue = json_decode( curl_exec( $sessionCurl ) );

        $url = 'https://dev.maxprograms.com/Validation/upload';

        $response = $this->httpPost( $url,
                [
                        'xliff' => new CURLFile( $xliff20, "application/xliff+xml", "file.xliff" )
                ],
                [
                        'Content-Type: multipart/form-data',
                        'schematron: no',
                        'session: ' . $sessionValue->session
                ]
        );

        if ( $response->info[ 'http_code' ] !== 200 ) {
            throw new Exception( ( $response->errorNo > 0 ) ? $response->error : 'An error occurred calling ' . $url . '. Status code ' . $response->info[ 'http_code' ] . ' was returned' );
        }

        $result = json_decode( $response->body );

        if ( $result->status == "error" ) {
            return [ $result->reason ];
        }

        return [];

    }
}
