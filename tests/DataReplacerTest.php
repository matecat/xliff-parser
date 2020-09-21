<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffUtils\DataRefReplacer;

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
        $expected = '<ph id="source1" dataRef="source1" equiv-text="${recipientName}"/> changed the address';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));

        // more complex test
        $map = [
            'source1' => '${recipientName}',
            'source2' => 'Babbo Natale',
            'source3' => 'La Befana',
        ];

        $string = '<ph id="source1" dataRef="source1"/> lorem <ec id="source2" dataRef="source2"/> ipsum <sc id="source3" dataRef="source3"/> changed';
        $expected = '<ph id="source1" dataRef="source1" equiv-text="${recipientName}"/> lorem <ec id="source2" dataRef="source2" equiv-text="Babbo Natale"/> ipsum <sc id="source3" dataRef="source3" equiv-text="La Befana"/> changed';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));

        // sample test
        $map = [
            "source3" => '&amp;lt;br&amp;gt;',
            "source34" => '&amp;lt;div&amp;gt;',
            "source45"> '&amp;lt;a href=&amp;s.uber.co =m /&amp;quot;&amp;gt;',
            "source30" => '&amp;lt;div&amp;gt;',
            "source41" => '&amp;lt;div&amp;gt;',
            "source52" => '&amp;lt;/div&amp;gt;',
            "source17" => '&amp;lt;div&amp;gt;',
            "source28" => '&amp;lt;div&amp;gt;',
            "source8" => '&amp;lt;br&amp;gt;',
            "source39" => '&amp;lt;/b&amp;gt;',
            "source13" => '&amp;lt;br&amp;gt;',
            "source24" => '&amp;lt;div&amp;gt;',
            "source4" => '&amp;lt;/div&amp;gt;',
            "source35" => '&amp;lt;br&amp;gt;',
            "source46" => '&amp;lt;/a&amp;gt;',
            "source20" => '&amp;lt;div&amp;gt;',
            "source31" => '&amp;lt;a href=&amp;s.uber.co =m /&amp;quot;&amp;gt;',
            "source42" => '&amp;lt;br&amp;gt;',
            "source53" => '&amp;lt;div&amp;gt;',
            "source18" => '&amp;lt;br&amp;gt;',
            "source29" => '&amp;lt;/div&amp;gt;',
            "source9" => '&amp;lt;/div&amp;gt;',
            "source14" => '&amp;lt;/div&amp;gt;',
            "source25" => '&amp;lt;b&amp;gt;',
            "source5" => '&amp;lt;div&amp;gt;',
            "source36" => '&amp;lt;/div&amp;gt;',
            "source47" => '&amp;lt;b&amp;gt;',
            "source10" => '&amp;lt;div&amp;gt;',
            "source21" => '&amp;lt;a href=&amp;quot;https://www.uber.com/s/voucher =s /&amp;quot;&amp;gt;',
            "source1" => '{Rider First Name}',
            "source32" => '&amp;lt;/a&amp;gt;',
            "source43" => '&amp;lt;/div&amp;gt;',
            "source54" => '&amp;lt;/div&amp;gt;',
            "source50" => '&amp;lt;div&amp;gt;',
            "source19" => '&amp;lt;/div&amp;gt;',
            "source15" => '&amp;lt;div&amp;gt;',
            "source26" => '&amp;lt;/b&amp;gt;',
            "source6" => '&amp;lt;/div&amp;gt;',
            "source37" => '&amp;lt;div&amp;gt;',
            "source48" => '&amp;lt;/b&amp;gt;',
            "source11" => '&amp;lt;/div&amp;gt;',
            "source22" => '&amp;lt;/a&amp;gt;',
            "source2" => '&amp;lt;div&amp;gt;',
            "source33" => '&amp;lt;/div&amp;gt;',
            "source44" => '&amp;lt;div&amp;gt;',
            "source40" => '&amp;lt;/div&amp;gt;',
            "source51" => '&amp;lt;br&amp;gt;',
            "source16" => '&amp;lt;/div&amp;gt;',
            "source27" => '&amp;lt;/div&amp;gt;',
            "source7" => '&amp;lt;div&amp;gt;',
            "source38" => '&amp;lt;b&amp;gt;',
            "source49" => '&amp;lt;/div&amp;gt;',
            "source12" => '&amp;lt;div&amp;gt;',
            "source23" => '&amp;lt;/div&amp;gt;'
        ];

        $string = 'Hi <ph id="source1" dataRef="source1"/>,<ph id="source2" dataRef="source2"/><ph id="source3" dataRef="source3"/><ph id="source4" dataRef="source4"/><ph id="source5" dataRef="source5"/>Thanks for reaching out.<ph id="source6" dataRef="source6"/><ph id="source7" dataRef="source7"/><ph id="source8" dataRef="source8"/><ph id="source9" dataRef="source9"/><ph id="source10" dataRef="source10"/>Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11"/><ph id="source12" dataRef="source12"/><ph id="source13" dataRef="source13"/><ph id="source14" dataRef="source14"/><ph id="source15" dataRef="source15"/>To start creating vouchers:<ph id="source16" dataRef="source16"/><ph id="source17" dataRef="source17"/><ph id="source18" dataRef="source18"/><ph id="source19" dataRef="source19"/><ph id="source20" dataRef="source20"/>1.';
        $expected = 'Hi <ph id="source1" dataRef="source1" equiv-text="{Rider First Name}"/>,<ph id="source2" dataRef="source2" equiv-text="&amp;lt;div&amp;gt;"/><ph id="source3" dataRef="source3" equiv-text="&amp;lt;br&amp;gt;"/><ph id="source4" dataRef="source4" equiv-text="&amp;lt;/div&amp;gt;"/><ph id="source5" dataRef="source5" equiv-text="&amp;lt;div&amp;gt;"/>Thanks for reaching out.<ph id="source6" dataRef="source6" equiv-text="&amp;lt;/div&amp;gt;"/><ph id="source7" dataRef="source7" equiv-text="&amp;lt;div&amp;gt;"/><ph id="source8" dataRef="source8" equiv-text="&amp;lt;br&amp;gt;"/><ph id="source9" dataRef="source9" equiv-text="&amp;lt;/div&amp;gt;"/><ph id="source10" dataRef="source10" equiv-text="&amp;lt;div&amp;gt;"/>Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" equiv-text="&amp;lt;/div&amp;gt;"/><ph id="source12" dataRef="source12" equiv-text="&amp;lt;div&amp;gt;"/><ph id="source13" dataRef="source13" equiv-text="&amp;lt;br&amp;gt;"/><ph id="source14" dataRef="source14" equiv-text="&amp;lt;/div&amp;gt;"/><ph id="source15" dataRef="source15" equiv-text="&amp;lt;div&amp;gt;"/>To start creating vouchers:<ph id="source16" dataRef="source16" equiv-text="&amp;lt;/div&amp;gt;"/><ph id="source17" dataRef="source17" equiv-text="&amp;lt;div&amp;gt;"/><ph id="source18" dataRef="source18" equiv-text="&amp;lt;br&amp;gt;"/><ph id="source19" dataRef="source19" equiv-text="&amp;lt;/div&amp;gt;"/><ph id="source20" dataRef="source20" equiv-text="&amp;lt;div&amp;gt;"/>1.';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function add_replaced_content_to_parsed_xliff_array()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber/56d591a5-louvre-v2-en_us-fr_fr-PM.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $expected = '<ph id="source1" dataRef="source1" equiv-text="&lt;p class=&quot;cmln__paragraph&quot;&gt;"/>The safety and well-being of everyone who uses Uber is at the heart of what we do.';

        $this->assertEquals($expected, trim($units[1]['source']['replaced-content'][0]));
        $this->assertEquals($expected, trim($units[1]['seg-source'][0]['replaced-content']));
    }
}