<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffUtils\DataRefReplacer;

class DataReplacerTest extends BaseTest
{
    /**
     * @test
     */
    public function do_nothing_with_empty_map()
    {
        $map = [];

        $string = 'Hai raccolto &lt;ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/&gt;&nbsp; da &lt;ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/&gt;?';
        $expected = 'Hai raccolto &lt;ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/&gt;&nbsp; da &lt;ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/&gt;?';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
    }

    /**
     * @test
     */
    public function can_replace_data()
    {
        $map = [
            'source1' => '${AMOUNT}',
            'source2' => '${RIDER}',
        ];

        $string = 'Hai raccolto &lt;ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/&gt;&nbsp; da &lt;ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/&gt;?';
        $expected = 'Hai raccolto &lt;ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/&gt;&nbsp; da &lt;ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/&gt;?';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data()
    {
        $map = [
                'source1' => '${AMOUNT}',
                'source2' => '${RIDER}',
        ];

        $string = 'Hai raccolto <ph id="source1" dataRef="source1"/>  da <ph id="source2" dataRef="source2"/>?';
        $expected = 'Hai raccolto <ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/>  da <ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/>?';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_2()
    {
        // sample test
        $map = [
            'source1' => '${recipientName}'
        ];

        $string = '<ph id="source1" dataRef="source1"/> changed the address';
        $expected = '<ph id="source1" dataRef="source1" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ=="/> changed the address';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_3()
    {
        // more complex test
        $map = [
                'source1' => '${recipientName}',
                'source2' => 'Babbo Natale',
                'source3' => 'La Befana',
        ];

        $string = '<ph id="source1" dataRef="source1"/> lorem <ec id="source2" dataRef="source2"/> ipsum <sc id="source3" dataRef="source3"/> changed';
        $expected = '<ph id="source1" dataRef="source1" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ=="/> lorem <ec id="source2" dataRef="source2" equiv-text="base64:QmFiYm8gTmF0YWxl"/> ipsum <sc id="source3" dataRef="source3" equiv-text="base64:TGEgQmVmYW5h"/> changed';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_4()
    {
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
        $expected = 'Hi <ph id="source1" dataRef="source1" equiv-text="base64:e1JpZGVyIEZpcnN0IE5hbWV9"/>,<ph id="source2" dataRef="source2" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow=="/><ph id="source3" dataRef="source3" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7"/><ph id="source4" dataRef="source4" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs="/><ph id="source5" dataRef="source5" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow=="/>Thanks for reaching out.<ph id="source6" dataRef="source6" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs="/><ph id="source7" dataRef="source7" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow=="/><ph id="source8" dataRef="source8" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7"/><ph id="source9" dataRef="source9" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs="/><ph id="source10" dataRef="source10" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow=="/>Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs="/><ph id="source12" dataRef="source12" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow=="/><ph id="source13" dataRef="source13" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7"/><ph id="source14" dataRef="source14" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs="/><ph id="source15" dataRef="source15" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow=="/>To start creating vouchers:<ph id="source16" dataRef="source16" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs="/><ph id="source17" dataRef="source17" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow=="/><ph id="source18" dataRef="source18" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7"/><ph id="source19" dataRef="source19" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs="/><ph id="source20" dataRef="source20" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow=="/>1.';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_5()
    {
        // sample test
        $map = [
            "source2" => '${RIDER}',
            "source3" => '&amp;lt;br&amp;gt;',
        ];

        $string = 'Hola <ph id="source1" dataRef="source1"/>';
        $expected = 'Hola <ph id="source1" dataRef="source1"/>';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_restore_data_with_no_matching_map_test()
    {
        // sample test
        $map = [
                "source2" => '${RIDER}',
                "source3" => '&amp;lt;br&amp;gt;',
        ];

        $string = 'Hola <ph id="source1" dataRef="source1" equiv-text=""/>';
        $expected = 'Hola <ph id="source1" dataRef="source1"/>';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->restore($string));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_6()
    {
        $map = [
                'source1' => '${Rider First Name}',
                'source2' => '&amp;lt;div&amp;',
        ];

        $string = 'Did you collect &lt;ph id="source1" dataRef="source1"/&gt; from &lt;ph id="source2" dataRef="source2"/&gt;?';
        $expected = 'Did you collect &lt;ph id="source1" dataRef="source1" equiv-text="base64:JHtSaWRlciBGaXJzdCBOYW1lfQ=="/&gt; from &lt;ph id="source2" dataRef="source2" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wOw=="/&gt;?';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_7()
    {
        $map = [
            'source1' => '${Rider First Name}',
            'source2' => '&amp;lt;div&amp;',
        ];

        $string = 'Did you collect &lt;ph id="source1" dataRef="source1" equiv-text="base64:"/&gt; from &lt;ph id="source2" dataRef="source2" equiv-text="base64:"/&gt;?';
        $expected = 'Did you collect &lt;ph id="source1" dataRef="source1" equiv-text="base64:JHtSaWRlciBGaXJzdCBOYW1lfQ=="/&gt; from &lt;ph id="source2" dataRef="source2" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wOw=="/&gt;?';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals('Did you collect &lt;ph id="source1" dataRef="source1"/&gt; from &lt;ph id="source2" dataRef="source2"/&gt;?', $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_8()
    {
        $map = [
                'source1' => '&lt;p class=&quot;cmln__paragraph&quot;&gt;',
                'source2' => '&amp;#39;',
                'source3' => '&lt;/p&gt;',
        ];

        // in this case string input has some wrong equiv-text
        $string = 'Hai <ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/>,<ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/><ph id="source3" dataRef="source3"/>';
        $expected = 'Hai <ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O3AgY2xhc3M9JnF1b3Q7Y21sbl9fcGFyYWdyYXBoJnF1b3Q7Jmd0Ow=="/>,<ph id="source2" dataRef="source2" equiv-text="base64:JmFtcDsjMzk7"/><ph id="source3" dataRef="source3" equiv-text="base64:Jmx0Oy9wJmd0Ow=="/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals('Hai <ph id="source1" dataRef="source1"/>,<ph id="source2" dataRef="source2"/><ph id="source3" dataRef="source3"/>', $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function add_replaced_content_to_parsed_xliff_array()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber/56d591a5-louvre-v2-en_us-fr_fr-PM.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $expected = '<ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O3AgY2xhc3M9JnF1b3Q7Y21sbl9fcGFyYWdyYXBoJnF1b3Q7Jmd0Ow=="/>The safety and well-being of everyone who uses Uber is at the heart of what we do.';

        $this->assertEquals($expected, trim($units[1]['source']['replaced-content'][0]));
        $this->assertEquals($expected, trim($units[1]['seg-source'][0]['replaced-content']));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_test_1()
    {
        $map = [
            'd1' => '[',
            'd2' => '](http://repubblica.it)',
        ];

        $string = 'Link semplice: <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>.';
        $expected = 'Link semplice: <ph id="1_1" dataRef="d1" equiv-text="base64:Ww==">La Repubblica<ph id="1_2" dataRef="d2" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk=">.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));

    }
}




