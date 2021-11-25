<?php

namespace Matecat\XliffParser\Tests;

use Faker\Factory;
use Matecat\XliffParser\Utils\Emoji;

class EmojiTest extends BaseTest
{
    /**
     * @test
     */
    public function doesNotTouchingOriginalTabs()
    {
        $string = 'La rana	in Spagna gracida in campagna';

        $this->assertEquals(Emoji::toEntity($string), $string);
    }

    /**
     * @test
     */
    public function canReplaceInvisibleGlyphs()
    {
        $string = '󠇡La rana in Spagna gracida in campagna';
        $expected = '&#917985;La rana in Spagna gracida in campagna';

        $this->assertEquals(Emoji::toEntity($string), $expected);
    }

    /**
     * @test
     */
    public function canReplaceEmojisWithEntites()
    {
        $dataset = [
            '🤙 Join this (video)call at: {{joinUrl}}' => '&#129305; Join this (video)call at: {{joinUrl}}',
            'Look 😀 It works! 🐻🌻' => 'Look &#128512; It works! &#128059;&#127803;',
            '􀄿' => '&#1048895;',
            '🗔' => '&#128468;',
            '👨' => '&#128104;',
            '🇺🇸' => '&#127482;&#127480;',
            '9️⃣' => '9&#65039;&#8419;',
            '👋🏻' => '&#128075;&#127995;',
            '🡪' => '&#129130;',
            '࿕' => '&#4053;',
            '⾮' => '&#12206;',
            '⌛'  => '&#8987;',
            '⏯'   => '&#9199;',
            'ༀ༁༂' => '&#3840;&#3841;&#3842;',
            '🪂' => '&#129666;',
            '𝑞' => '&#119902;',
            '𝑖' => '&#119894;',
            '𝑽' => '&#119933;',
            '𝑹' => '&#119929;',
            '𝑺' => '&#119930;',
            '𝑻' => '&#119931;',
            '𝑰' => '&#119920;',
            '𝑴' => '&#119924;',
            '𝑆' => '&#119878;',
            '𝒄' => '&#119940;',
            '𝒐' => '&#119952;',
            '𝒔' => '&#119956;',
            '𝑷' => '&#119927;',
            '𝑸' => '&#119928;',
            '𝑨' => '&#119912;',
            '𧈧'  => '&#160295;',
        ];

        foreach ($dataset as $emoji => $entity) {
            $this->assertEquals($entity, Emoji::toEntity($emoji));
        }
    }

    /**
     * Perform 100000 iterations to test script performance
     *
     * @test
     */
    public function performanceTest()
    {
        $faker = Factory::create();

        for ($i=0;$i<100000;$i++){
            $this->assertNotEquals('', Emoji::toEntity($faker->emoji));
        }
    }
}