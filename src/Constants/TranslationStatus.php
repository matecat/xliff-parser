<?php

namespace Matecat\XliffParser\Constants;

/**
 * This is a copy of
 * https://github.com/matecat/MateCat/blob/develop/lib/Utils/Constants/TranslationStatus.php
 */
class TranslationStatus {
    const STATUS_NEW        = 'NEW';
    const STATUS_DRAFT      = 'DRAFT';
    const STATUS_TRANSLATED = 'TRANSLATED';
    const STATUS_APPROVED   = 'APPROVED';
    const STATUS_REJECTED   = 'REJECTED';
    const STATUS_FIXED      = 'FIXED';
    const STATUS_REBUTTED   = 'REBUTTED';

    public static $DB_STATUSES_MAP = [
            self::STATUS_NEW        => 1,
            self::STATUS_DRAFT      => 2,
            self::STATUS_TRANSLATED => 3,
            self::STATUS_APPROVED   => 4,
            self::STATUS_REJECTED   => 5,
            self::STATUS_FIXED      => 6,
            self::STATUS_REBUTTED   => 7
    ];

    public static $STATUSES = [
            self::STATUS_NEW,
            self::STATUS_DRAFT,
            self::STATUS_TRANSLATED,
            self::STATUS_APPROVED,
            self::STATUS_REBUTTED,
    ];

    public static $INITIAL_STATUSES = [
            self::STATUS_NEW,
            self::STATUS_DRAFT
    ];

    public static $TRANSLATION_STATUSES = [
            self::STATUS_TRANSLATED
    ];


    public static $REVISION_STATUSES = [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED
    ];

    public static $POST_REVISION_STATUSES = [
            self::STATUS_FIXED,
            self::STATUS_REBUTTED
    ];

    public static function isReviewedStatus( $status ) {
        return in_array( $status, TranslationStatus::$REVISION_STATUSES );
    }
}
