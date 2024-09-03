<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 06/08/24
 * Time: 15:37
 *
 */

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\XliffReplacer\StatusToStateAttribute;

class StatusToStateAttributeTest extends BaseTest {

    /**
     * @Test
     */
    public function testTranslatedStatus() {

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_TRANSLATED );
        $this->assertEquals( "state=\"translated\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_TRANSLATED, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 2, TranslationStatus::STATUS_TRANSLATED );
        $this->assertEquals( "state=\"translated\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_TRANSLATED, $lastMrkState );


        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_TRANSLATED, TranslationStatus::STATUS_APPROVED );
        $this->assertEquals( "state=\"translated\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_TRANSLATED, $lastMrkState );


        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_TRANSLATED, TranslationStatus::STATUS_TRANSLATED );
        $this->assertEquals( "state=\"translated\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_TRANSLATED, $lastMrkState );


        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_TRANSLATED, TranslationStatus::STATUS_APPROVED2 );
        $this->assertEquals( "state=\"translated\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_TRANSLATED, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_TRANSLATED, TranslationStatus::STATUS_DRAFT );
        $this->assertEquals( "state=\"new\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_DRAFT, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 2, TranslationStatus::STATUS_TRANSLATED, TranslationStatus::STATUS_DRAFT );
        $this->assertEquals( "state=\"initial\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_DRAFT, $lastMrkState );

    }

    /**
     * @Test
     */
    public function testDraftStatus() {
        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_DRAFT, TranslationStatus::STATUS_APPROVED2 );
        $this->assertEquals( "state=\"new\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_DRAFT, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_DRAFT, TranslationStatus::STATUS_NEW );
        $this->assertEquals( "state=\"new\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_NEW, $lastMrkState );

    }

    /**
     * @Test
     */
    public function testRevisionStatus() {

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_APPROVED2 );
        $this->assertEquals( "state=\"final\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_APPROVED2, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_APPROVED2, TranslationStatus::STATUS_APPROVED2 );
        $this->assertEquals( "state=\"final\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_APPROVED2, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_APPROVED, TranslationStatus::STATUS_APPROVED2 );
        $this->assertEquals( "state=\"signed-off\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_APPROVED, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, TranslationStatus::STATUS_APPROVED2, TranslationStatus::STATUS_DRAFT );
        $this->assertEquals( "state=\"new\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_DRAFT, $lastMrkState );

    }

    /**
     * @Test
     */
    public function testNullStatus() {

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, null, '' );
        $this->assertEquals( "state=\"final\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_APPROVED2, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, null, TranslationStatus::STATUS_APPROVED2 );
        $this->assertEquals( "state=\"final\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_APPROVED2, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, null, TranslationStatus::STATUS_DRAFT );
        $this->assertEquals( "state=\"new\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_DRAFT, $lastMrkState );

        [ $stateProp, $lastMrkState ] = StatusToStateAttribute::getState( 1, null, null );
        $this->assertEquals( "state=\"final\"", $stateProp );
        $this->assertEquals( TranslationStatus::STATUS_APPROVED2, $lastMrkState );

    }

}
