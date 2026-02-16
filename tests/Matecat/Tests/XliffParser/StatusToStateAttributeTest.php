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
        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, TranslationStatus::TRANSLATED);
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::TRANSLATED, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(2, TranslationStatus::TRANSLATED);
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::TRANSLATED, $lastMrkState);


        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::TRANSLATED,
            TranslationStatus::APPROVED
        );
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::TRANSLATED, $lastMrkState);


        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::TRANSLATED,
            TranslationStatus::TRANSLATED
        );
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::TRANSLATED, $lastMrkState);


        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::TRANSLATED,
            TranslationStatus::APPROVED2
        );
        $this->assertEquals("state=\"translated\"", $stateProp);
        $this->assertEquals(TranslationStatus::TRANSLATED, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::TRANSLATED,
            TranslationStatus::DRAFT
        );
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::DRAFT, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            2,
            TranslationStatus::TRANSLATED,
            TranslationStatus::DRAFT
        );
        $this->assertEquals("state=\"initial\"", $stateProp);
        $this->assertEquals(TranslationStatus::DRAFT, $lastMrkState);
    }

    #[Test]
    public function testDraftStatus(): void
    {
        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::DRAFT,
            TranslationStatus::APPROVED2
        );
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::DRAFT, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::DRAFT,
            TranslationStatus::NEW
        );
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::NEW, $lastMrkState);
    }

    #[Test]
    public function testRevisionStatus(): void
    {
        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, TranslationStatus::APPROVED2);
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::APPROVED2, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::APPROVED2,
            TranslationStatus::APPROVED2
        );
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::APPROVED2, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::APPROVED,
            TranslationStatus::APPROVED2
        );
        $this->assertEquals("state=\"signed-off\"", $stateProp);
        $this->assertEquals(TranslationStatus::APPROVED, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(
            1,
            TranslationStatus::APPROVED2,
            TranslationStatus::DRAFT
        );
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::DRAFT, $lastMrkState);
    }

    #[Test]
    public function testNullStatus(): void
    {
        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, null, '');
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::APPROVED2, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, null, TranslationStatus::APPROVED2);
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::APPROVED2, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1, null, TranslationStatus::DRAFT);
        $this->assertEquals("state=\"new\"", $stateProp);
        $this->assertEquals(TranslationStatus::DRAFT, $lastMrkState);

        [$stateProp, $lastMrkState] = StatusToStateAttribute::getState(1);
        $this->assertEquals("state=\"final\"", $stateProp);
        $this->assertEquals(TranslationStatus::APPROVED2, $lastMrkState);
    }

}
