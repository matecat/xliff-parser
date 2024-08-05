<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 02/08/24
 * Time: 13:11
 *
 */

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Constants\TranslationStatus;

class StatusToStateAttribute {


    /**
     * @param string  $status
     * @param int     $xliffVersion
     * @param ?string $state_prop
     * @param ?string $lastMrkState
     *
     * @return array
     */
    public static function getState( string $status, int $xliffVersion, ?string $state_prop = '', ?string $lastMrkState = '' ): array {

        switch ( $status ) {

            case TranslationStatus::STATUS_FIXED:
            case TranslationStatus::STATUS_APPROVED2:
                if ( $lastMrkState == null || $lastMrkState == TranslationStatus::STATUS_APPROVED2 ) {
                    $state_prop   = "state=\"final\"";
                    $lastMrkState = TranslationStatus::STATUS_APPROVED2;
                }
                break;
            case TranslationStatus::STATUS_APPROVED:
                if ( $lastMrkState == null || $lastMrkState == TranslationStatus::STATUS_APPROVED ) {
                    $state_prop   = ( $xliffVersion === 2 ) ? "state=\"reviewed\"" : "state=\"signed-off\"";
                    $lastMrkState = TranslationStatus::STATUS_APPROVED;
                }
                break;

            case TranslationStatus::STATUS_TRANSLATED:
                if ( $lastMrkState == null || $lastMrkState == TranslationStatus::STATUS_TRANSLATED || $lastMrkState == TranslationStatus::STATUS_APPROVED ) {
                    $state_prop   = "state=\"translated\"";
                    $lastMrkState = TranslationStatus::STATUS_TRANSLATED;
                }
                break;

            case TranslationStatus::STATUS_REJECTED:  // if there is a mark REJECTED and there is not a DRAFT, all the trans-unit is REJECTED. In V2 there is no way to mark
            case TranslationStatus::STATUS_REBUTTED:
                if ( ( $lastMrkState == null ) || ( $lastMrkState != TranslationStatus::STATUS_NEW || $lastMrkState != TranslationStatus::STATUS_DRAFT ) ) {
                    $state_prop   = ( $xliffVersion === 2 ) ? "state=\"initial\"" : "state=\"needs-review-translation\"";
                    $lastMrkState = TranslationStatus::STATUS_REJECTED;
                }
                break;

            case TranslationStatus::STATUS_NEW:
                if ( ( $lastMrkState == null ) || $lastMrkState != TranslationStatus::STATUS_NEW ) {
                    $state_prop   = ( $xliffVersion === 2 ) ? "state=\"initial\"" : "state=\"new\"";
                    $lastMrkState = TranslationStatus::STATUS_NEW;
                }
                break;

            case TranslationStatus::STATUS_DRAFT:
                if ( ( $lastMrkState == null ) || $lastMrkState != TranslationStatus::STATUS_DRAFT ) {
                    $state_prop   = ( $xliffVersion === 2 ) ? "state=\"initial\"" : "state=\"new\"";
                    $lastMrkState = TranslationStatus::STATUS_DRAFT;
                }
                break;

            default:
                // this is the case when a segment is not showed in cattool, so the row in
                // segment_translations does not exists and
                // ---> $seg[ 'status' ] is NULL
                if ( $lastMrkState == null ) { //this is the first MRK ID
                    $state_prop   = "state=\"translated\"";
                    $lastMrkState = TranslationStatus::STATUS_TRANSLATED;
                } else {
                    /* Do nothing and preserve the last state */
                }
                break;
        }

        return [ $state_prop, $lastMrkState ];

    }

}