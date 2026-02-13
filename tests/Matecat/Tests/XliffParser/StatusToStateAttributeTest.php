<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Constants\TranslationStatus;
use Matecat\XliffParser\XliffReplacer\StatusToStateAttribute;
use PHPUnit\Framework\Attributes\Test;

class StatusToStateAttributeTest extends Base
{

    #[Test]
    public function testTranslatedStatus(): void
    {
        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, TranslationStatus::STATUS_TRANSLATED);
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_TRANSLATED, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(2, TranslationStatus::STATUS_TRANSLATED);
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_TRANSLATED, $lastMrkState);


        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::STATUS_TRANSLATED,
            TranslationStatus::STATUS_APPROVED
        );
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_TRANSLATED, $lastMrkState);


        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::STATUS_TRANSLATED,
            TranslationStatus::STATUS_TRANSLATED
        );
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_TRANSLATED, $lastMrkState);


        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::STATUS_TRANSLATED,
            TranslationStatus::STATUS_APPROVED2
        );
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_TRANSLATED, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::STATUS_TRANSLATED,
            TranslationStatus::STATUS_DRAFT
        );
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_DRAFT, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            2,
            TranslationStatus::STATUS_TRANSLATED,
            TranslationStatus::STATUS_DRAFT
        );
        $this->assertEquals("state=\"initial\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_DRAFT, $lastMrkState);
    }

    #[Test]
    public function testDraftStatus(): void
    {
        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::STATUS_DRAFT,
            TranslationStatus::STATUS_APPROVED2
        );
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_DRAFT, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::STATUS_DRAFT,
            TranslationStatus::STATUS_NEW
        );
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_NEW, $lastMrkState);
    }

    #[Test]
    public function testRevisionStatus(): void
    {
        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, TranslationStatus::STATUS_APPROVED2);
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_APPROVED2, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::STATUS_APPROVED2,
            TranslationStatus::STATUS_APPROVED2
        );
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_APPROVED2, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::STATUS_APPROVED,
            TranslationStatus::STATUS_APPROVED2
        );
        $this->assertEquals("state=\"signed-off\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_APPROVED, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::STATUS_APPROVED2,
            TranslationStatus::STATUS_DRAFT
        );
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_DRAFT, $lastMrkState);
    }

    #[Test]
    public function testNullStatus(): void
    {
        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, null, '');
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_APPROVED2, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, null, TranslationStatus::STATUS_APPROVED2);
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_APPROVED2, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, null, TranslationStatus::STATUS_DRAFT);
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_DRAFT, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1);
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::STATUS_APPROVED2, $lastMrkState);
    }

}
