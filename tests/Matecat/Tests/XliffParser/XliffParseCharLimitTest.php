<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;
use PHPUnit\Framework\Attributes\Test;

class XliffParseCharLimitTest extends Base {

    #[Test]
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

    #[Test]
    public function should_get_xliff_v2_char_limit() {
        $parsed = ( new XliffParser() )->xliffToArray( $this->getTestFile( 'char-limit.xliff' ) );
        $attr   = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'attr' ];

        $this->assertEquals( 55, $attr[ 'sizeRestriction' ] );
    }

}