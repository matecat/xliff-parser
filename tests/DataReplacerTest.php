<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\Utils\DataRefReplacer;
use Matecat\XliffParser\Utils\Files;

class DataReplacerTest extends BaseTest
{
    /**
     * @test
     */
    public function can_replace_data_in_a_string()
    {
        // sample test
        $map = [
            'source1' => '${recipientName}'
        ];

        $string = '<ph id="source1" dataRef="source1"/> changed the address';
        $expected = '${recipientName} changed the address';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));

        // more complex test
        $map = [
                'source1' => '${recipientName}',
                'source2' => 'Babbo Natale',
                'source3' => 'La Befana',
        ];

        $string = '<ph id="source1" dataRef="source1"/> lorem <ec id="source2" dataRef="source2"/> ipsum <sc id="source3" dataRef="source3"/> changed';
        $expected = '${recipientName} lorem Babbo Natale ipsum La Befana changed';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
    }
}