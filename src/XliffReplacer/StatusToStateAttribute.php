<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffReplacer;

use Matecat\XliffParser\Constants\TranslationStatus;

final class StatusToStateAttribute
{
    // XLIFF state attribute constants
    private const string STATE_INITIAL = 'state="initial"';
    private const string STATE_FINAL = 'state="final"';
    private const string STATE_TRANSLATED = 'state="translated"';
    private const string STATE_REVIEWED = 'state="reviewed"';
    private const string STATE_SIGNED_OFF = 'state="signed-off"';
    private const string STATE_NEEDS_REVIEW_TRANSLATION = 'state="needs-review-translation"';
    private const string STATE_NEW = 'state="new"';

    /**
     * Get state attribute and status for a segment.
     *
     * @param int $xliffVersion XLIFF version (1 or 2)
     * @param string|null $status Current segment status
     * @param string|null $lastMrkState Last mark state
     *
     * @return array{0: string, 1: string} State property string and status
     */
    public static function getState(
        int $xliffVersion,
        ?string $status = null,
        ?string $lastMrkState = null
    ): array {
        $status = empty($status) ? TranslationStatus::STATUS_APPROVED2 : $status;

        $stateLevelsMap = [
            TranslationStatus::STATUS_APPROVED2 => 100,
            TranslationStatus::STATUS_APPROVED => 90,
            TranslationStatus::STATUS_TRANSLATED => 80,
            TranslationStatus::STATUS_REJECTED => 70,
            TranslationStatus::STATUS_DRAFT => 60,
            TranslationStatus::STATUS_NEW => 50
        ];

        $orderedValues = array_flip($stateLevelsMap);

        // Define state mappings for different statuses
        $stateMap = [
            TranslationStatus::STATUS_APPROVED2 => [self::STATE_FINAL, TranslationStatus::STATUS_APPROVED2],
            TranslationStatus::STATUS_APPROVED => [
                ($xliffVersion === 2) ? self::STATE_REVIEWED : self::STATE_SIGNED_OFF,
                TranslationStatus::STATUS_APPROVED
            ],
            TranslationStatus::STATUS_TRANSLATED => [self::STATE_TRANSLATED, TranslationStatus::STATUS_TRANSLATED],
            TranslationStatus::STATUS_REJECTED => [
                ($xliffVersion === 2) ? self::STATE_INITIAL : self::STATE_NEEDS_REVIEW_TRANSLATION,
                TranslationStatus::STATUS_REJECTED
            ],
            TranslationStatus::STATUS_NEW => [
                ($xliffVersion === 2) ? self::STATE_INITIAL : self::STATE_NEW,
                TranslationStatus::STATUS_NEW
            ],
            TranslationStatus::STATUS_DRAFT => [
                ($xliffVersion === 2) ? self::STATE_INITIAL : self::STATE_NEW,
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
            $stateLevelsMap[$status],
            ($stateLevelsMap[$lastMrkState] ?? $stateLevelsMap[TranslationStatus::STATUS_NEW])
        );

        // If the last mark state is set, get the minimum value, otherwise get the current state
        [
            $state_prop,
            $lastMrkState
        ] = empty($lastMrkState) ? $stateMap[$status] : $stateMap[$orderedValues[$minStatus]];

        return [$state_prop, $lastMrkState];
    }

}
