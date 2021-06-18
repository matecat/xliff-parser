<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffUtils\DataRefReplacer;

class DataReplacerTest extends BaseTest
{
    /**
     * @test
     */
    public function can_add_id_to_ph_ec_sc_when_is_missing()
    {
        $map = [
            'd1' => '&lt;x/&gt;',
            'd2' => '&lt;br\/&gt;',
        ];

        $string = '<ph dataRef="d1" id="d1"/><ec dataRef="d2" startRef="5" subType="xlf:b" type="fmt"/>';
        $expected = '<ph dataRef="d1" id="d1" equiv-text="base64:Jmx0O3gvJmd0Ow=="/><ph dataRef="d2" startRef="5" subType="xlf:b" type="fmt" id="d2" removeId="true" dataType="ec" equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/>';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_ph_with_same_ids()
    {
        $map = [
                'd1' => '&lt;br\/&gt;',
        ];

        $string = 'San Francisco, CA<ph dataRef="d1" id="1" subType="xlf:lb" type="fmt" />650 California St, Ste 2950<ph dataRef="d1" id="2" subType="xlf:lb" type="fmt" />San Francisco<ph dataRef="d1" id="3" subType="xlf:lb" type="fmt" />CA 94108';
        $expected = 'San Francisco, CA<ph dataRef="d1" id="1" subType="xlf:lb" type="fmt"  equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/>650 California St, Ste 2950<ph dataRef="d1" id="2" subType="xlf:lb" type="fmt"  equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/>San Francisco<ph dataRef="d1" id="3" subType="xlf:lb" type="fmt"  equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/>CA 94108';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function do_nothing_with_ph_tags_without_dataref()
    {
        $map = [
            'source3' => '&lt;/a&gt;',
            'source4' => '&lt;br&gt;',
            'source5' => '&lt;br&gt;',
            'source1' => '&lt;br&gt;',
            'source2' => '&lt;a href=%s&gt;',
        ];

        $string = 'Hi <ph id="mtc_1" equiv-text="base64:JXM="/> .';
        $expected = 'Hi <ph id="mtc_1" equiv-text="base64:JXM="/> .';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
    }

    /**
     * @test
     */
    public function do_nothing_with_empty_map()
    {
        $map = [];

        $string = '<ph id=\"mtc_1\" equiv-text=\"base64:Jmx0O2gyJmd0Ow==\"/>Aanvullende richtlijnen voor hosts van privékamers en gedeelde ruimtes<ph id=\"mtc_2\" equiv-text=\"base64:Jmx0Oy9oMiZndDs=\"/> <ph id=\"mtc_3\" equiv-text=\"base64:Jmx0O3AmZ3Q7\"/>Hosts van privékamers of gedeelde ruimtes moeten ook:<ph id=\"mtc_4\" equiv-text=\"base64:Jmx0Oy9wJmd0Ow==\"/> <ph id=\"mtc_5\" equiv-text=\"base64:Jmx0O3VsJmd0Ow==\"/> <ph id=\"mtc_6\" equiv-text=\"base64:Jmx0O2xpJmd0Ow==\"/>het aantal gasten beperken om sociale afstand in alle gemeenschappelijke ruimtes mogelijk<ph id=\"mtc_7\" equiv-text=\"base64:Jmx0Oy9saSZndDs=\"/> <ph id=\"mtc_8\" equiv-text=\"base64:Jmx0O2xpJmd0Ow==\"/>te maken Beperk de ruimtes waartoe gasten toegang hebben, om onnodige blootstelling voor u en uw gasten<ph id=\"mtc_9\" equiv-text=\"base64:Jmx0Oy9saSZndDs=\"/> <ph id=\"mtc_10\" equiv-text=\"base64:Jmx0O2xpJmd0Ow==\"/>Ventileer gemeenschappelijke ruimtes tijdens het verblijf, indien veilig en beveiligd, zoals gespecificeerd in het<ph id=\"mtc_11\" equiv-text=\"base64:Jmx0Oy9saSZndDs=\"/> <ph id=\"mtc_12\" equiv-text=\"base64:Jmx0O2xpJmd0Ow==\"/>schoonmaakprotocol Reinig en reinig gemeenschappelijke ruimtes (zoals badkamers en keukens) zo vaak mogelijk<ph id=\"mtc_13\" equiv-text=\"base64:Jmx0Oy9saSZndDs=\"/> <ph id=\"mtc_14\" equiv-text=\"base64:Jmx0Oy91bCZndDs=\"/> <ph id=\"mtc_15\" equiv-text=\"base64:Jmx0O3AmZ3Q7\"/>Sommige overheden kunnen beperkingen opleggen aan het hosten van privé- of gedeelde kamers of kan aan die ruimten aanvullende verplichtingen of eisen stellen.';
        $expected = '<ph id=\"mtc_1\" equiv-text=\"base64:Jmx0O2gyJmd0Ow==\"/>Aanvullende richtlijnen voor hosts van privékamers en gedeelde ruimtes<ph id=\"mtc_2\" equiv-text=\"base64:Jmx0Oy9oMiZndDs=\"/> <ph id=\"mtc_3\" equiv-text=\"base64:Jmx0O3AmZ3Q7\"/>Hosts van privékamers of gedeelde ruimtes moeten ook:<ph id=\"mtc_4\" equiv-text=\"base64:Jmx0Oy9wJmd0Ow==\"/> <ph id=\"mtc_5\" equiv-text=\"base64:Jmx0O3VsJmd0Ow==\"/> <ph id=\"mtc_6\" equiv-text=\"base64:Jmx0O2xpJmd0Ow==\"/>het aantal gasten beperken om sociale afstand in alle gemeenschappelijke ruimtes mogelijk<ph id=\"mtc_7\" equiv-text=\"base64:Jmx0Oy9saSZndDs=\"/> <ph id=\"mtc_8\" equiv-text=\"base64:Jmx0O2xpJmd0Ow==\"/>te maken Beperk de ruimtes waartoe gasten toegang hebben, om onnodige blootstelling voor u en uw gasten<ph id=\"mtc_9\" equiv-text=\"base64:Jmx0Oy9saSZndDs=\"/> <ph id=\"mtc_10\" equiv-text=\"base64:Jmx0O2xpJmd0Ow==\"/>Ventileer gemeenschappelijke ruimtes tijdens het verblijf, indien veilig en beveiligd, zoals gespecificeerd in het<ph id=\"mtc_11\" equiv-text=\"base64:Jmx0Oy9saSZndDs=\"/> <ph id=\"mtc_12\" equiv-text=\"base64:Jmx0O2xpJmd0Ow==\"/>schoonmaakprotocol Reinig en reinig gemeenschappelijke ruimtes (zoals badkamers en keukens) zo vaak mogelijk<ph id=\"mtc_13\" equiv-text=\"base64:Jmx0Oy9saSZndDs=\"/> <ph id=\"mtc_14\" equiv-text=\"base64:Jmx0Oy91bCZndDs=\"/> <ph id=\"mtc_15\" equiv-text=\"base64:Jmx0O3AmZ3Q7\"/>Sommige overheden kunnen beperkingen opleggen aan het hosten van privé- of gedeelde kamers of kan aan die ruimten aanvullende verplichtingen of eisen stellen.';

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

        $dataReplacer = new DataRefReplacer($map);

        $string = 'Hai raccolto &lt;ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/&gt;&nbsp; da &lt;ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/&gt;?';
        $expected = 'Hai raccolto &lt;ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/&gt;&nbsp; da &lt;ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/&gt;?';

        $this->assertEquals($expected, $dataReplacer->replace($string));

        $string = 'Ai colectat &lt;ph id=\"source1\" dataRef=\"source1\"/&gt; din &lt;ph id=\"source2\" dataRef=\"source2\"/&gt;?';
        $expected = 'Ai colectat &lt;ph id=\"source1\" dataRef=\"source1\" equiv-text="base64:JHtBTU9VTlR9"/&gt; din &lt;ph id=\"source2\" dataRef=\"source2\" equiv-text="base64:JHtSSURFUn0="/&gt;?';

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

        $string = '<ec id="source1" dataRef="source1"/> changed the address';
        $expected = '<ph id="source1" dataRef="source1" dataType="ec" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ=="/> changed the address';
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
        $expected = '<ph id="source1" dataRef="source1" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ=="/> lorem <ph id="source2" dataRef="source2" dataType="ec" equiv-text="base64:QmFiYm8gTmF0YWxl"/> ipsum <ph id="source3" dataRef="source3" dataType="sc" equiv-text="base64:TGEgQmVmYW5h"/> changed';
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
        $expected = 'Link semplice: <ph id="1_1" dataType="pcStart" originalData="PHBjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDEiPg==" dataRef="d1" equiv-text="base64:Ww=="/>La Repubblica<ph id="1_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d2" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk="/>.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_test_2()
    {
        $map = [
            'd1' => '[',
            'd2' => '](http://repubblica.it)',
            'd3' => '[',
            'd4' => '](http://google.it)',
        ];

        $string = 'Link semplici: <pc id="1" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc> <pc id="2" dataRefEnd="d3" dataRefStart="d4">Google</pc>.';
        $expected = 'Link semplici: <ph id="1_1" dataType="pcStart" originalData="PHBjIGlkPSIxIiBkYXRhUmVmRW5kPSJkMiIgZGF0YVJlZlN0YXJ0PSJkMSI+" dataRef="d1" equiv-text="base64:Ww=="/>La Repubblica<ph id="1_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d2" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk="/> <ph id="2_1" dataType="pcStart" originalData="PHBjIGlkPSIyIiBkYXRhUmVmRW5kPSJkMyIgZGF0YVJlZlN0YXJ0PSJkNCI+" dataRef="d4" equiv-text="base64:XShodHRwOi8vZ29vZ2xlLml0KQ=="/>Google<ph id="2_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d3" equiv-text="base64:Ww=="/>.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_test_3()
    {
        $map = [
            'd1' => '[',
            'd2' => '](http://repubblica.it)',
            'd3' => '[',
            'd4' => '](http://google.it)',
            'source1' => '${Rider First Name}',
            'source2' => '&amp;lt;div&amp;',
        ];

        $string = 'Did you collect <ph id="source1" dataRef="source1"/> from <ph id="source2" dataRef="source2"/>? Link semplici: <pc id="1" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc> <pc id="2" dataRefEnd="d3" dataRefStart="d4">Google</pc>.';
        $expected = 'Did you collect <ph id="source1" dataRef="source1" equiv-text="base64:JHtSaWRlciBGaXJzdCBOYW1lfQ=="/> from <ph id="source2" dataRef="source2" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wOw=="/>? Link semplici: <ph id="1_1" dataType="pcStart" originalData="PHBjIGlkPSIxIiBkYXRhUmVmRW5kPSJkMiIgZGF0YVJlZlN0YXJ0PSJkMSI+" dataRef="d1" equiv-text="base64:Ww=="/>La Repubblica<ph id="1_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d2" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk="/> <ph id="2_1" dataType="pcStart" originalData="PHBjIGlkPSIyIiBkYXRhUmVmRW5kPSJkMyIgZGF0YVJlZlN0YXJ0PSJkNCI+" dataRef="d4" equiv-text="base64:XShodHRwOi8vZ29vZ2xlLml0KQ=="/>Google<ph id="2_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d3" equiv-text="base64:Ww=="/>.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_test_5()
    {
        $map = [
                'd1' => '[',
                'd2' => '](http://repubblica.it)',
                'd3' => '[',
                'd4' => '](http://google.it)',
        ];

        $string = 'Link semplici: &lt;pc id="1" dataRefEnd="d2" dataRefStart="d1"&gt;La Repubblica&lt;/pc&gt;';
        $expected = 'Link semplici: &lt;ph id="1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSIxIiBkYXRhUmVmRW5kPSJkMiIgZGF0YVJlZlN0YXJ0PSJkMSImZ3Q7" dataRef="d1" equiv-text="base64:Ww=="/&gt;La Repubblica&lt;ph id="1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d2" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk="/&gt;';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function do_not_affect_not_matecat_ph_tags_with_equiv_text()
    {
        $dataReplacer = new DataRefReplacer([
            'source1' => '&lt;br&gt;',
        ]);

        $string = 'Hi <ph id="mtc_1" equiv-text="JXM="/>, <ph id="source1" dataRef="source1"/>You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';
        $expected = 'Hi <ph id="mtc_1" equiv-text="JXM="/>, <ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O2JyJmd0Ow=="/>You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));

        $string = 'Hi &lt;ph id="mtc_1" equiv-text="JXM="/&gt;, <ph id="source1" dataRef="source1"/>You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';
        $expected = 'Hi &lt;ph id="mtc_1" equiv-text="JXM="/&gt;, <ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O2JyJmd0Ow=="/>You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));

        $string = 'Hi &lt;ph id="mtc_1" equiv-text="JXM="/&gt;, &lt;ph id="source1" dataRef="source1"/&gt;You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';
        $expected = 'Hi &lt;ph id="mtc_1" equiv-text="JXM="/&gt;, &lt;ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O2JyJmd0Ow=="/&gt;You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_nested_pc_tags()
    {
        $map = [
                'd1' => '_',
                'd2' => '**',
                'd3' => '`',
        ];

        $string = 'Testo libero contenente <pc id="3" dataRefEnd="d1" dataRefStart="d1"><pc id="4" dataRefEnd="d2" dataRefStart="d2">grassetto + corsivo</pc></pc>';
        $expected = 'Testo libero contenente <ph id="3_1" dataType="pcStart" originalData="PHBjIGlkPSIzIiBkYXRhUmVmRW5kPSJkMSIgZGF0YVJlZlN0YXJ0PSJkMSI+" dataRef="d1" equiv-text="base64:Xw=="/><ph id="4_1" dataType="pcStart" originalData="PHBjIGlkPSI0IiBkYXRhUmVmRW5kPSJkMiIgZGF0YVJlZlN0YXJ0PSJkMiI+" dataRef="d2" equiv-text="base64:Kio="/>grassetto + corsivo<ph id="4_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d2" equiv-text="base64:Kio="/><ph id="3_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d1" equiv-text="base64:Xw=="/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_escaped_nested_pc_tags()
    {
        $map = [
                'd1' => '_',
                'd2' => '**',
                'd3' => '`',
        ];

        $string = 'Testo libero contenente &lt;pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1"&gt;corsivo&lt;/pc&gt;, &lt;pc id="2" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2"&gt;grassetto&lt;/pc&gt;, &lt;pc id="3" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1"&gt;&lt;pc id="4" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2"&gt;grassetto + corsivo&lt;/pc&gt;&lt;/pc&gt; e &lt;pc id="5" canCopy="no" canDelete="no" dataRefEnd="d3" dataRefStart="d3"&gt;larghezza fissa&lt;/pc&gt;.';
        $expected = 'Testo libero contenente &lt;ph id="1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow==" dataRef="d1" equiv-text="base64:Xw=="/&gt;corsivo&lt;ph id="1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d1" equiv-text="base64:Xw=="/&gt;, &lt;ph id="2_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSIyIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDIiJmd0Ow==" dataRef="d2" equiv-text="base64:Kio="/&gt;grassetto&lt;ph id="2_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d2" equiv-text="base64:Kio="/&gt;, &lt;ph id="3_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSIzIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow==" dataRef="d1" equiv-text="base64:Xw=="/&gt;&lt;ph id="4_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSI0IiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDIiJmd0Ow==" dataRef="d2" equiv-text="base64:Kio="/&gt;grassetto + corsivo&lt;ph id="4_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d2" equiv-text="base64:Kio="/&gt;&lt;ph id="3_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d1" equiv-text="base64:Xw=="/&gt; e &lt;ph id="5_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSI1IiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDMiIGRhdGFSZWZTdGFydD0iZDMiJmd0Ow==" dataRef="d3" equiv-text="base64:YA=="/&gt;larghezza fissa&lt;ph id="5_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d3" equiv-text="base64:YA=="/&gt;.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_and_ph_matecat_tags()
    {
        $map = [
                'source1' => '[',
        ];

        $string = 'Text <pc id="source1" dataRefStart="source1" dataRefEnd="source1"><ph id="mtc_2" equiv-text="base64:Yg=="/>Uber Community Guidelines<ph id="mtc_3" equiv-text="base64:Yg=="/></pc>.';
        $expected = 'Text <ph id="source1_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiPg==" dataRef="source1" equiv-text="base64:Ww=="/><ph id="mtc_2" equiv-text="base64:Yg=="/>Uber Community Guidelines<ph id="mtc_3" equiv-text="base64:Yg=="/><ph id="source1_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source1" equiv-text="base64:Ww=="/>.';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_and_ph_real_matecat_tags()
    {
        $map = [
                'source1' => '&lt;w:hyperlink r:id="rId6"&gt;&lt;/w:hyperlink&gt;',
        ];

        $string = 'This code of conduct sets forth the minimum standards by which Uber’s Driver Partners must adhere when using the Uber app in Czech Republic, in addition to the terms of their services agreement with Uber and the &lt;pc id="source1" dataRefStart="source1" dataRefEnd="source1"&gt;&lt;ph id="mtc_2" equiv-text="base64:Jmx0O3BjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiJmd0Ow=="/&gt;Uber Community Guidelines&lt;ph id="mtc_3" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;&lt;/pc&gt;.';
        $expected = 'This code of conduct sets forth the minimum standards by which Uber’s Driver Partners must adhere when using the Uber app in Czech Republic, in addition to the terms of their services agreement with Uber and the &lt;ph id="source1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiJmd0Ow==" dataRef="source1" equiv-text="base64:Jmx0O3c6aHlwZXJsaW5rIHI6aWQ9InJJZDYiJmd0OyZsdDsvdzpoeXBlcmxpbmsmZ3Q7"/&gt;&lt;ph id="mtc_2" equiv-text="base64:Jmx0O3BjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiJmd0Ow=="/&gt;Uber Community Guidelines&lt;ph id="mtc_3" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;&lt;ph id="source1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source1" equiv-text="base64:Jmx0O3c6aHlwZXJsaW5rIHI6aWQ9InJJZDYiJmd0OyZsdDsvdzpoeXBlcmxpbmsmZ3Q7"/&gt;.';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_restore_data_with_pc_from_matecat_real_case()
    {
        $map = [
                'd1' => '_',
                'd2' => '**',
                'd3' => '`',
        ];

        $string = 'Testo libero contenente <ph id="1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow==" dataRef="d1" equiv-text="base64:Xw=="/>';
        $expected = 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">';

        $dataRefReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataRefReplacer->restore($string));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_missing_dataRefStart()
    {
        $map = [
                'd1' => '&lt;br\/&gt;',
        ];

        $string = 'Text <pc id="d1" dataRefStart="d1">Uber Community Guidelines</pc>.';
        $expected = 'Text <ph id="d1_1" dataType="pcStart" originalData="PHBjIGlkPSJkMSIgZGF0YVJlZlN0YXJ0PSJkMSI+" dataRef="d1" equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/>Uber Community Guidelines<ph id="d1_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d1" equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/>.';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_missing_dataRefEnd()
    {
        $map = [
                'd1' => '&lt;br\/&gt;',
        ];

        $string = 'Text <pc id="d1" dataRefEnd="d1">Uber Community Guidelines</pc>.';
        $expected = 'Text <ph id="d1_1" dataType="pcStart" originalData="PHBjIGlkPSJkMSIgZGF0YVJlZkVuZD0iZDEiPg==" dataRef="d1" equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/>Uber Community Guidelines<ph id="d1_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d1" equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/>.';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_non_standard_characters()
    {
        $map = [
                "source3" => "<g id=\"jcP-TFFSO2CSsuLt\" ctype=\"x-html-strong\" \/>",
                "source4" => "<g id=\"5StCYYRvqMc0UAz4\" ctype=\"x-html-ul\" \/>",
                "source5" => "<g id=\"99phhJcEQDLHBjeU\" ctype=\"x-html-li\" \/>",
                "source1" => "<g id=\"lpuxniQlIW3KrUyw\" ctype=\"x-html-p\" \/>",
                "source6" => "<g id=\"0HZug1d3LkXJU04E\" ctype=\"x-html-li\" \/>",
                "source2" => "<g id=\"d3TlPtomlUt0Ej1k\" ctype=\"x-html-p\" \/>",
                "source7" => "<g id=\"oZ3oW_0KaicFXFDS\" ctype=\"x-html-li\" \/>"
        ];

        // this string contains ’
        $string = '&lt;pc id="source4" dataRefStart="source4"&gt;The rider can’t tell if the driver matched the profile picture.&lt;/pc&gt;';
        $expected = '&lt;ph id="source4_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2U0IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTQiJmd0Ow==" dataRef="source4" equiv-text="base64:PGcgaWQ9IjVTdENZWVJ2cU1jMFVBejQiIGN0eXBlPSJ4LWh0bWwtdWwiIFwvPg=="/&gt;The rider can’t tell if the driver matched the profile picture.&lt;ph id="source4_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source4" equiv-text="base64:PGcgaWQ9IjVTdENZWVJ2cU1jMFVBejQiIGN0eXBlPSJ4LWh0bWwtdWwiIFwvPg=="/&gt;';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_nested_pc_structures()
    {
        $map = [
            "source1" => "x",
            "source2" => "y",
        ];

        $string = '<pc id="source1" dataRefStart="source1">foo <pc id="source2" dataRefStart="source2">bar</pc> baz</pc>';
        $expected = '<ph id="source1_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg==" dataRef="source1" equiv-text="base64:eA=="/>foo <ph id="source2_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg==" dataRef="source2" equiv-text="base64:eQ=="/>bar<ph id="source2_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source2" equiv-text="base64:eQ=="/> baz<ph id="source1_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source1" equiv-text="base64:eA=="/>';

        $dataReplacer = new DataRefReplacer($map);


        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_more_complex_nested_pc_structures()
    {
        $map = [
            "source1" => "x",
            "source2" => "y",
            "source3" => "z",
            "source4" => "a",
            "source5" => "b",
        ];

        $string = '<pc id="source1" dataRefStart="source1">foo <pc id="source2" dataRefStart="source2">bar lorem</pc> <pc id="source3" dataRefStart="source3">bar <pc id="source4" dataRefStart="source4">bar</pc> <pc id="source5" dataRefStart="source5">bar</pc></pc> cavolino</pc>';
        $expected = '<ph id="source1_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg==" dataRef="source1" equiv-text="base64:eA=="/>foo <ph id="source2_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg==" dataRef="source2" equiv-text="base64:eQ=="/>bar lorem<ph id="source2_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source2" equiv-text="base64:eQ=="/> <ph id="source3_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2UzIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTMiPg==" dataRef="source3" equiv-text="base64:eg=="/>bar <ph id="source4_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2U0IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTQiPg==" dataRef="source4" equiv-text="base64:YQ=="/>bar<ph id="source4_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source4" equiv-text="base64:YQ=="/> <ph id="source5_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2U1IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTUiPg==" dataRef="source5" equiv-text="base64:Yg=="/>bar<ph id="source5_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source5" equiv-text="base64:Yg=="/><ph id="source3_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source3" equiv-text="base64:eg=="/> cavolino<ph id="source1_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source1" equiv-text="base64:eA=="/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_sc_and_ex_tags()
    {
        $map = [
                "d1" => "&lt;strong&gt;",
                "d2" => "&lt;\/strong&gt;",
                "d3" => "&lt;br\/&gt;",
                "d4" => "&lt;a href=\"mailto:info@elysiancollection.com\"&gt;",
                "d5" => "&lt;\/a&gt;",
        ];

        $string = '<sc dataRef="d1" id="1" subType="xlf:b" type="fmt"/>Elysian Collection<ph dataRef="d3" id="2" subType="xlf:lb" type="fmt"/><ec dataRef="d2" startRef="1" subType="xlf:b" type="fmt"/>Bahnhofstrasse 15, Postfach 341, Zermatt CH- 3920, Switzerland<ph dataRef="d3" id="3" subType="xlf:lb" type="fmt"/>Tel: +44 203 468 2235  Email: <pc dataRefEnd="d5" dataRefStart="d4" id="4" type="link">info@elysiancollection.com</pc><sc dataRef="d1" id="5" subType="xlf:b" type="fmt"/><ph dataRef="d3" id="6" subType="xlf:lb" type="fmt"/><ec dataRef="d2" startRef="5" subType="xlf:b" type="fmt"/>';
        $expected = '<ph dataRef="d1" id="1" subType="xlf:b" type="fmt" dataType="sc" equiv-text="base64:Jmx0O3N0cm9uZyZndDs="/>Elysian Collection<ph dataRef="d3" id="2" subType="xlf:lb" type="fmt" equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/><ph dataRef="d2" startRef="1" subType="xlf:b" type="fmt" id="d2" removeId="true" dataType="ec" equiv-text="base64:Jmx0O1wvc3Ryb25nJmd0Ow=="/>Bahnhofstrasse 15, Postfach 341, Zermatt CH- 3920, Switzerland<ph dataRef="d3" id="3" subType="xlf:lb" type="fmt" equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/>Tel: +44 203 468 2235  Email: <ph id="4_1" dataType="pcStart" originalData="PHBjIGRhdGFSZWZFbmQ9ImQ1IiBkYXRhUmVmU3RhcnQ9ImQ0IiBpZD0iNCIgdHlwZT0ibGluayI+" dataRef="d4" equiv-text="base64:Jmx0O2EgaHJlZj0ibWFpbHRvOmluZm9AZWx5c2lhbmNvbGxlY3Rpb24uY29tIiZndDs="/>info@elysiancollection.com<ph id="4_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d5" equiv-text="base64:Jmx0O1wvYSZndDs="/><ph dataRef="d1" id="5" subType="xlf:b" type="fmt" dataType="sc" equiv-text="base64:Jmx0O3N0cm9uZyZndDs="/><ph dataRef="d3" id="6" subType="xlf:lb" type="fmt" equiv-text="base64:Jmx0O2JyXC8mZ3Q7"/><ph dataRef="d2" startRef="5" subType="xlf:b" type="fmt" id="d2" removeId="true" dataType="ec" equiv-text="base64:Jmx0O1wvc3Ryb25nJmd0Ow=="/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function do_not_duplicate_equiv_text_in_ph_tags()
    {
        $map = [
            'source1' => '%s',
        ];

        $string = 'Hi <ph id="source1" dataRef="d1" equiv-text="base64:JXM="/> .';
        $expected = 'Hi <ph id="source1" dataRef="d1" equiv-text="base64:JXM="/> .';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function do_not_duplicate_equiv_text_in_already_transformed_pc_tags()
    {
        $map = [
            "d2" => "&lt;/a&gt;",
        ];

        $string = '&lt;ph id="1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d2" equiv-text="base64:Jmx0Oy9hJmd0Ow=="/&gt;';
        $expected = '&lt;ph id="1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d2" equiv-text="base64:Jmx0Oy9hJmd0Ow=="/&gt;';
        $restored = '&lt;/pc&gt;';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($restored, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_parse_nested_not_mapped_pc()
    {
        $map = [
            "source1" => "a",
            "source2" => "b",
            "source3" => "c",
        ];

        $string = '<pc id="source1" dataRefStart="source1">April 24, 2017</pc> | Written by <pc id="source2" dataRefStart="source2"><pc id="1b" type="fmt" subType="m:b">Troy Stevenson</pc></pc><pc id="source3" dataRefStart="source3">,</pc> Global Head of Community Operations';
        $expected = '<ph id="source1_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg==" dataRef="source1" equiv-text="base64:YQ=="/>April 24, 2017<ph id="source1_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source1" equiv-text="base64:YQ=="/> | Written by <ph id="source2_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg==" dataRef="source2" equiv-text="base64:Yg=="/><pc id="1b" type="fmt" subType="m:b">Troy Stevenson</pc><ph id="source2_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source2" equiv-text="base64:Yg=="/><ph id="source3_1" dataType="pcStart" originalData="PHBjIGlkPSJzb3VyY2UzIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTMiPg==" dataRef="source3" equiv-text="base64:Yw=="/>,<ph id="source3_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="source3" equiv-text="base64:Yw=="/> Global Head of Community Operations';
        $restored = '<pc id="source1" dataRefStart="source1">April 24, 2017</pc> | Written by <pc id="source2" dataRefStart="source2"><pc id="1b" type="fmt" subType="m:b">Troy Stevenson</pc></pc><pc id="source3" dataRefStart="source3">,</pc> Global Head of Community Operations';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($restored, $dataReplacer->restore($expected));
    }
}


