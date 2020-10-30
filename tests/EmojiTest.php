<?php

namespace Matecat\XliffParser\Tests;

use Faker\Factory;
use Matecat\XliffParser\Utils\Emoji;

class EmojiTest extends BaseTest
{
    /**
     * @test
     */
    public function canReplaceEmojisWithEntites()
    {
        $dataset = [
            '🤙 Join this (video)call at: {{joinUrl}}' => '&#129305; Join this (video)call at: {{joinUrl}}',
            'Look 😀 It works! 🐻🌻' => 'Look &#128512; It works! &#128059;&#127803;',
            '🗔' => '&#128468;',
            '👨' => '&#128104;',
            '🇺🇸' => '&#127482;&#127480;',
            '9️⃣' => '&#57;&#65039;&#8419;',
            '👋🏻' => '&#128075;&#127995;',
        ];

        foreach ($dataset as $emoji => $entity) {
            $this->assertEquals($entity, Emoji::toEntity($emoji));
        }
    }

    /**
     * @test
     */
    public function performance()
    {
        $faker = Factory::create();

        for ($i=0;$i<100000;$i++){
            $this->assertNotEquals('', Emoji::toEntity($faker->emoji));
        }
    }
}