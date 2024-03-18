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
            '󠇯' => '&#917999;',
            '🪄' => '&#129668;',
            '􀎵' => '&#1049525;',
            '󠄀'   => '&#917760;',
            '󠇡'   => '&#917985;',
            '󠄞'  => '&#917790;',
            '󠆌'   => '&#917900;',
            '🤙 Join this (video)call at: {{joinUrl}}' => '&#129305; Join this (video)call at: {{joinUrl}}',
            'Look 😀 It works! 🐻🌻' => 'Look &#128512; It works! &#128059;&#127803;',
            '🪵'  => '&#129717;',
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
            "󠅸" => '&#917880;',
            '𧈧'  => '&#160295;',
            '🪴' => '&#129716;',
            '🫖' => '&#129750;',
            '🫒' => '&#129746;',
            '🪟' => '&#129695;',
            '󰀄' => '&#983044;',
            '􀃆' => '&#1048774;',
            '🪩' => '&#129705;',
            '􀅖' => '&#1048918;',
            '🪙' => '&#129689;',
            '􀀇' => '&#1048583;',
            '􀀊' => '&#1048586;',
            '􀀋' => '&#1048587;',
            '􀀌' => '&#1048588;',
            '🛜' => '&#128732;',
            '􀀂'  => '&#1048578;',
            '𡞱' => '&#137137;',
            "󠄟" => "&#917791;",
            "🪫" => '&#129707;',
            '🫶' => '&#129782;',
            '🪅' => '&#129669;',
            '🩷' => '&#129655;',
            '🪞' => '&#129694;',
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

    /**
     * Perform 100000 iterations to test script performance
     *
     * @test
     */
    public function entityToEmojiTest() {
        $segment = 'Questo &#10005; è un emoji a croce &#1048918;&#129689; manina &#128075;&#127995;';
        $this->assertEquals( 'Questo ✕ è un emoji a croce 􀅖🪙 manina 👋🏻', Emoji::toEmoji( $segment ) );
    }

    /**
     * This test is performed on the same emoji (flexed arm) in different shades
     *
     * @test
     */
    public function canEncodeAndDecodeDifferentShades()
    {
        $emojis = [
            '🤌',
            '💪🏻',
            '💪🏽',
            '💪🏾',
            '💪🏿',
            '💪🏿'
        ];

        foreach ($emojis as $i => $emoji){

            $entity = Emoji::toEntity($emoji);
            $toEmoji = Emoji::toEmoji($entity);

            $this->assertEquals($emojis[$i], $toEmoji);
        }
    }
}