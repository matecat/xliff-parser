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
        $status = empty($status) ? TranslationStatus::APPROVED2 : $status;

        $stateLevelsMap = [
            TranslationStatus::APPROVED2 => 100,
            TranslationStatus::APPROVED => 90,
            TranslationStatus::TRANSLATED => 80,
            TranslationStatus::REJECTED => 70,
            TranslationStatus::DRAFT => 60,
            TranslationStatus::NEW => 50
        ];

        $orderedValues = array_flip($stateLevelsMap);

        // Define state mappings for different statuses
        $stateMap = [
            TranslationStatus::APPROVED2 => [self::STATE_FINAL, TranslationStatus::APPROVED2],
            TranslationStatus::APPROVED => [
                ($xliffVersion === 2) ? self::STATE_REVIEWED : self::STATE_SIGNED_OFF,
                TranslationStatus::APPROVED
            ],
            TranslationStatus::TRANSLATED => [self::STATE_TRANSLATED, TranslationStatus::TRANSLATED],
            TranslationStatus::REJECTED => [
                ($xliffVersion === 2) ? self::STATE_INITIAL : self::STATE_NEEDS_REVIEW_TRANSLATION,
                TranslationStatus::REJECTED
            ],
            TranslationStatus::NEW => [
                ($xliffVersion === 2) ? self::STATE_INITIAL : self::STATE_NEW,
                TranslationStatus::NEW
            ],
            TranslationStatus::DRAFT => [
                ($xliffVersion === 2) ? self::STATE_INITIAL : self::STATE_NEW,
                TranslationStatus::DRAFT
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
            ($stateLevelsMap[$lastMrkState] ?? $stateLevelsMap[TranslationStatus::NEW])
        );

        // If the last mark state is set, get the minimum value, otherwise get the current state
        [
            $state_prop,
            $lastMrkState
        ] = empty($lastMrkState) ? $stateMap[$status] : $stateMap[$orderedValues[$minStatus]];

        return [$state_prop, $lastMrkState];
    }

}
