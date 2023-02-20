<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 05/01/23
 * Time: 15:50
 *
 */

namespace Matecat\XliffParser\Tests;


use Matecat\XliffParser\Exception\InvalidXmlException;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\Exception\XmlParsingException;
use Matecat\XliffParser\XliffParser;

class XliffParseCharLimitTest extends BaseTest {

    /**
     * @test
     * @throws InvalidXmlException
     * @throws NotValidFileException
     * @throws XmlParsingException
     * @throws NotSupportedVersionException
     */
    public function should_get_xliff_v1_char_limit() {

        $parsed = ( new XliffParser() )->xliffToArray( $this->getTestFile( 'char-limit.jsont2.xlf' ) );

        $this->assertTrue( !empty( $parsed ) );
        $this->assertTrue( isset( $parsed[ 'files' ] ) );
        $this->assertCount( 4, $parsed[ 'files' ] );
        $this->assertTrue( isset( $parsed[ 'files' ][ 3 ][ 'trans-units' ] ) );
        $this->assertCount( 2, $parsed[ 'files' ][ 3 ][ 'trans-units' ] );
        $this->assertTrue( isset( $parsed[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'attr' ] ) );
        $this->assertCount( 5, $parsed[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'attr' ] );

        $this->assertArrayHasKey( "sizeRestriction", $parsed[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'attr' ] );
        $this->assertArrayHasKey( "maxwidth", $parsed[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'attr' ] );
        $this->assertArrayHasKey( "size-unit", $parsed[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'attr' ] );

        $this->assertEquals( 55, $parsed[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'attr' ][ 'sizeRestriction' ] );
        $this->assertEquals( 55, $parsed[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'attr' ][ 'maxwidth' ] );
        $this->assertEquals( 'char', $parsed[ 'files' ][ 3 ][ 'trans-units' ][ 1 ][ 'attr' ][ 'size-unit' ] );

    }

    /**
     * @test
     * @return void
     * @throws InvalidXmlException
     * @throws NotSupportedVersionException
     * @throws NotValidFileException
     * @throws XmlParsingException
     */
    public function should_get_xliff_v2_char_limit() {
        $parsed = ( new XliffParser() )->xliffToArray( $this->getTestFile( 'char-limit.xliff' ) );
        $attr   = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'attr' ];

        $this->assertEquals( 55, $attr[ 'sizeRestriction' ] );
    }

}