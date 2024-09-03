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
     * @param int         $xliffVersion
     * @param string|null $status
     * @param string|null $lastMrkState
     *
     * @return array
     */
    public static function getState(
            int     $xliffVersion,
            ?string $status = null,
            ?string $lastMrkState = null
    ): array {

        $status = empty( $status ) ? TranslationStatus::STATUS_APPROVED2 : $status;

        $stateLevelsMap = [
                TranslationStatus::STATUS_APPROVED2  => 100,
                TranslationStatus::STATUS_APPROVED   => 90,
                TranslationStatus::STATUS_TRANSLATED => 80,
                TranslationStatus::STATUS_REJECTED   => 70,
                TranslationStatus::STATUS_DRAFT      => 60,
                TranslationStatus::STATUS_NEW        => 50
        ];

        $orderedValues = array_flip( $stateLevelsMap );

        // Define state mappings for different statuses
        $stateMap = [
                TranslationStatus::STATUS_APPROVED2  => [ "state=\"final\"", TranslationStatus::STATUS_APPROVED2 ],
                TranslationStatus::STATUS_APPROVED   => [
                        ( $xliffVersion === 2 ) ? "state=\"reviewed\"" : "state=\"signed-off\"",
                        TranslationStatus::STATUS_APPROVED
                ],
                TranslationStatus::STATUS_TRANSLATED => [ "state=\"translated\"", TranslationStatus::STATUS_TRANSLATED ],
                TranslationStatus::STATUS_REJECTED   => [
                        ( $xliffVersion === 2 ) ? "state=\"initial\"" : "state=\"needs-review-translation\"",
                        TranslationStatus::STATUS_REJECTED
                ],
                TranslationStatus::STATUS_NEW        => [
                        ( $xliffVersion === 2 ) ? "state=\"initial\"" : "state=\"new\"",
                        TranslationStatus::STATUS_NEW
                ],
                TranslationStatus::STATUS_DRAFT      => [
                        ( $xliffVersion === 2 ) ? "state=\"initial\"" : "state=\"new\"",
                        TranslationStatus::STATUS_DRAFT
                ],
        ];

        // If status is null we set the default status value as Approved2 because in this way
        // it will not affect the result of the min() function.
        // This is the case when a segment is not shown in the cattool,
        // and the row in segment_translations does not exists.
        // ---> $seg[ 'status' ] is NULL
        // If lastMrkState is empty
        $minStatus = min(
                $stateLevelsMap[ $status ],
                ( $stateLevelsMap[ $lastMrkState ] ?? $stateLevelsMap[ TranslationStatus::STATUS_NEW ] )
        );

        // If the last mark state is set, get the minimum value, otherwise get the current state
        [ $state_prop, $lastMrkState ] = empty( $lastMrkState ) ? $stateMap[ $status ] : $stateMap[ $orderedValues[ $minStatus ] ];

        return [ $state_prop, $lastMrkState ];

    }

}