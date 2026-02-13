<?php

declare(strict_types=1);

namespace Matecat\XliffParser\Tests;

use Exception;
use Matecat\XliffParser\Exception\NotFoundIdInTransUnit;
use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;
use Matecat\XliffParser\Exception\SegmentIdTooLongException;
use Matecat\XliffParser\XliffParser;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

class XliffParserV2Test extends Base
{
    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_a_xliff_file_with_empty_size_restriction_metadata(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/size-restriction.xliff'));

        $this->assertFalse(isset($parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ] [ 'attr' ] ['sizeRestriction']));
        $this->assertEquals( 60, $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 14 ] [ 'attr' ]['sizeRestriction'] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_a_xliff_file_with_empty_pc_tags(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/pc-slash.xlf'));
        $transUnits = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ];
        $expected1 = '<pc id="source4" dataRefStart="source4"><pc id="source5" dataRefStart="source5">She will learn if the user has received at least <pc id="source6" dataRefStart="source6">one notification</pc> for a Dangerous Driving report for tickets other than ticket 1.</pc></pc><pc id="source7" dataRefStart="source7"><pc id="source8" dataRefStart="source8"></pc></pc><pc id="source9" dataRefStart="source9"><pc id="source10" dataRefStart="source10">When she checked, Hilary found that this customer has 2 prior reports!</pc></pc>';
        $expected2 = '<pc id="source14" dataRefStart="source14"><pc id="source15" dataRefStart="source15"><pc id="source16" dataRefStart="source16"><pc id="source17" dataRefStart="source17"></pc></pc></pc><pc id="source18" dataRefStart="source18"><pc id="source19" dataRefStart="source19"><pc id="source20" dataRefStart="source20">Adjudicate based on the information contained in the Bliss contact supplied in the DACT JIRA; do not access duplicate Bliss contacts.</pc></pc></pc><pc id="source21" dataRefStart="source21"><pc id="source22" dataRefStart="source22"><pc id="source23" dataRefStart="source23"><pc id="source24" dataRefStart="source24">Review All JIRA and Bliss tickets</pc> in the Description section for each incident.</pc></pc></pc><pc id="source25" dataRefStart="source25"><pc id="source26" dataRefStart="source26"><pc id="source27" dataRefStart="source27">Consider all allegations and counter-allegations made against a user meeting any of the above definitions</pc><pc id="source28" dataRefStart="source28"><pc id="source29" dataRefStart="source29"> in the Count decision.</pc></pc></pc></pc></pc>';

        $this->assertEquals($expected1, $transUnits[ 'source' ] [ 'raw-content' ] [ 0 ]);
        $this->assertEquals($expected2, $transUnits[ 'source' ] [ 'raw-content' ] [ 1 ]);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_a_xliff_file_collapsing_empty_tags(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/pc-slash.xlf'), true);
        $transUnits = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ];
        $expected = '<pc id="source14" dataRefStart="source14"><pc id="source15" dataRefStart="source15"><pc id="source16" dataRefStart="source16"><pc id="source17" dataRefStart="source17"/></pc></pc><pc id="source18" dataRefStart="source18"><pc id="source19" dataRefStart="source19"><pc id="source20" dataRefStart="source20">Adjudicate based on the information contained in the Bliss contact supplied in the DACT JIRA; do not access duplicate Bliss contacts.</pc></pc></pc><pc id="source21" dataRefStart="source21"><pc id="source22" dataRefStart="source22"><pc id="source23" dataRefStart="source23"><pc id="source24" dataRefStart="source24">Review All JIRA and Bliss tickets</pc> in the Description section for each incident.</pc></pc></pc><pc id="source25" dataRefStart="source25"><pc id="source26" dataRefStart="source26"><pc id="source27" dataRefStart="source27">Consider all allegations and counter-allegations made against a user meeting any of the above definitions</pc><pc id="source28" dataRefStart="source28"><pc id="source29" dataRefStart="source29"> in the Count decision.</pc></pc></pc></pc></pc>';

        $this->assertEquals($expected, $transUnits[ 'source' ] [ 'raw-content' ] [ 1 ]);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_empty_xliff_v2(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/empty.xliff'));

        $this->assertFalse(isset( $parsed[ 'files' ][ 1 ][ 'trans-units' ]));
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_with_encoded_g_tags_in_originalData(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/xliff_20_with_g_tags_in_dataref.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $originalData = $units[1]['original-data'][0];

        $this->assertEquals( '&lt;g id="0PEY7rmSqeVk51Xn" ctype="x-html-strong" /&gt;', $originalData['raw-content'] );
        $this->assertEquals( 'd1', $originalData['attr']['id'] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_with_new_line_values_in_originalData(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/blank-dataRef.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertArrayHasKey('original-data', $units[4]);
        $this->assertEquals( '  ', $units[4]['original-data'][0]['raw-content'] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_with_pc_tags(): void
    {
        // <pc> tags do not be escaped here
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/1111_prova.md.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals('Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.', $units[2][ 'source' ]['raw-content'][0]);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_with_double_encoded_map(): void
    {
        // &amp;#39;
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/uber/39.xliff.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals("&lt;p class=&quot;cmln__paragraph&quot;&gt;", $units[4][ 'original-data' ][1]['raw-content']);
        $this->assertEquals("&#39;", $units[4][ 'original-data' ][2]['raw-content']);
        $this->assertEquals("&lt;/p&gt;", $units[4][ 'original-data' ][0]['raw-content']);

        // &amp;amp;
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/uber/&.xliff.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals("&amp;", $units[2][ 'original-data' ][7]['raw-content']);

        // &amp;apos;
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/uber/apos.xliff.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals("&apos;", $units[5][ 'original-data' ][0]['raw-content']);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_metadata(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/uber-v2.xliff'));
        $attr   = $parsed[ 'files' ][ 1 ][ 'attr' ];

        $this->assertCount(3, $attr);
        $this->assertEquals( 'en-us', $attr[ 'source-language' ] );
        $this->assertEquals( 'el-gr', $attr[ 'target-language' ] );
        $this->assertEquals( '389108a4-rtapi.xml', $attr[ 'original' ] );
        $this->assertEmpty($parsed[ 'files' ][ 1 ][ 'notes' ]);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_notes(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/sample-20.xlf'));
        $notes  = $parsed[ 'files' ][ 1 ][ 'notes' ];

        $this->assertCount(3, $notes);
        $this->assertEquals( 'note for file.', $notes[ 0 ][ 'raw-content' ] );
        $this->assertEquals( 'note2 for file.', $notes[ 1 ][ 'raw-content' ] );
        $this->assertEquals( '{
                    "key": "value",
                    "key2": "value2",
                    "key3": "value3"
                }', $notes[ 2 ][ 'json' ] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_trans_units_metadata(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/sample-20.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertCount(2, $units);
        $this->assertEquals( 'u1', $units[ 1 ][ 'attr' ][ 'id' ] );
        $this->assertEquals( 'yes', $units[ 1 ][ 'attr' ][ 'translate' ] );
        $this->assertEquals( 'u2', $units[ 2 ][ 'attr' ][ 'id' ] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_trans_units_originalData(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/uber-v2.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals( '${redemptionLimit}', $units[ 5 ][ 'original-data' ][ 0 ][ 'raw-content' ] );
        $this->assertEquals( 'source1', $units[ 5 ][ 'original-data' ][ 0 ][ 'attr' ][ 'id' ] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_trans_units_notes(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/uber-v2.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];
        $note   = $units[ 1 ][ 'notes' ];

        $this->assertEquals( 'note for unit', $note[ 0 ][ 'raw-content' ] );
        $this->assertEquals( 'another note for file.', $note[ 1 ][ 'raw-content' ] );
        $this->assertEquals( [
                'raw-content' => '01d35857-b9bd-4835-8db1-40febcdcc8e9',
                'attr'        => [
                        'type' => 'key'
                ]
        ], $note[ 2 ] );
        $this->assertEquals( [
                'raw-content' => 'Repo: &lt;a href ="https://i18n.uberinternal.com/rosetta2/repo/rtapi/keys" target="_blank"&gt;rtapi&lt;/a&gt; Key Name: &lt;a href="https://i18n.uberinternal.com/rosetta2/repo/rtapi/key/driver_tasks.delivery_reminders.order.wda.title/overview" target="_blank"&gt;driver_tasks.delivery_reminders.order.wda.title&lt;/a&gt; Description: &lt;font color="blue"&gt;Title for delivery reminders when an eater has changed the dropoff location for an order&lt;/font&gt;',
                'attr'        => [
                        'type' => 'key-note'
                ]
        ], $note[ 3 ] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_trans_units_source_and_target(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/sample-20.xlf'));

        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertCount(2, $units);

        // avoid assertStringContainsString because in PHPUnit 5 doesn't exist
        $this->assertStringContainsString( '<pc id="1">Hello <mrk id="m1" type="term">World</mrk>!</pc>', $units[ 1 ][ 'source' ][ 'raw-content' ][ 0 ] );
        $this->assertStringContainsString( '<pc id="1">Hello <mrk id="m1" type="term">World</mrk>!</pc>', $units[ 1 ][ 'source' ][ 'raw-content' ][ 0 ] );
        $this->assertStringContainsString( '<pc id="2">Hello <mrk id="m2" type="term">World2</mrk>!</pc>', $units[ 2 ][ 'source' ][ 'raw-content' ][ 0 ] );
        $this->assertStringContainsString( '<pc id="1">Bonjour le <mrk id="m1" type="term">Monde</mrk> !</pc>', $units[ 1 ][ 'target' ][ 'raw-content' ][ 0 ] );
        $this->assertStringContainsString( '<pc id="2">Bonjour le <mrk id="m2" type="term">Monde2</mrk> !</pc>', $units[ 2 ][ 'target' ][ 'raw-content' ][ 0 ] );
        $this->assertEquals( [], $units[ 1 ][ 'source' ][ 'attr' ] );
        $this->assertEquals( [], $units[ 2 ][ 'source' ][ 'attr' ] );
        $this->assertEquals( [], $units[ 1 ][ 'target' ][ 'attr' ] );
        $this->assertEquals( [], $units[ 2 ][ 'target' ][ 'attr' ] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_trans_units_nested_in_groups(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/sample-20-with-group.xlf'));

        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];
        $this->assertCount(3, $units);
        $this->assertStringContainsString('Sentence from a group', $units[ 1 ][ 'source' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Phrase from a group', $units[ 1 ][ 'target' ][ 'raw-content' ][0]);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_with_ti_in_nested_groups(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/sample-20-with-nested-group.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertStringContainsString('Sentence 1. ', $units[ 1 ][ 'source' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Phrase 1. ', $units[ 1 ][ 'target' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Sentence 2. ', $units[ 2 ][ 'source' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Phrase 2. ', $units[ 2 ][ 'target' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Sentence 3. ', $units[ 2 ][ 'source' ][ 'raw-content' ][1]);
        $this->assertStringContainsString('Phrase 3. ', $units[ 2 ][ 'target' ][ 'raw-content' ][1]);
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_trans_units_segmented_source_and_target(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/sample-20-segmented.xlf'));

        $source  = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ]['source'];
        $target  = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ]['target'];

        $this->assertEquals( 'Sentence 1. ', $source['raw-content'][0] );
        $this->assertEquals( 'Phrase 1. ', $target['raw-content'][0] );
        $this->assertEquals( 'Sentence 2. ', $source['raw-content'][1] );
        $this->assertEquals( 'Phrase 2. ', $target['raw-content'][1] );
        $this->assertEquals( 'Sentence 3. ', $source['raw-content'][2] );
        $this->assertEquals( 'Phrase 3. ', $target['raw-content'][2] );
        $this->assertEquals( '<pc id="1">pc</pc> Sentence 4.', $source['raw-content'][3] );
        $this->assertEquals( 'Phrase 4.', $target['raw-content'][3] );

        $this->assertEquals( [
            'xml:space' => 'space',
            'xml:lang' => 'fr',
        ], $source['attr'][ 3 ] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_trans_units_segmented_seg_source_and_seg_target(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/sample-20-segmented.xlf'));

        $segSource  = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ]['seg-source'];
        $segTarget  = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ]['seg-target'];

        $this->assertEquals( 'Sentence 1. ', $segSource[0]['raw-content'] );
        $this->assertEquals( 'Phrase 1. ', $segTarget[0]['raw-content'] );
        $this->assertEquals( 'Sentence 2. ', $segSource[1]['raw-content'] );
        $this->assertEquals( 'Phrase 2. ', $segTarget[1]['raw-content'] );
        $this->assertEquals( 'Sentence 3. ', $segSource[2]['raw-content'] );
        $this->assertEquals( 'Phrase 3. ', $segTarget[2]['raw-content'] );
        $this->assertEquals( '<pc id="1">pc</pc> Sentence 4.', $segSource[3]['raw-content'] );
        $this->assertEquals( 'Phrase 4.', $segTarget[3]['raw-content'] );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2_with_ec_and_sc(): void
    {
        // <pc> tags do not be escaped here
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/pcec.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertArrayHasKey('source', $units[4]);
        $this->assertEquals( '
                    <sc dataRef="d1" id="1" subType="xlf:b" type="fmt"/>Elysian Collection<ph dataRef="d3" id="2" subType="xlf:lb" type="fmt"/><ec dataRef="d2" startRef="1" subType="xlf:b" type="fmt"/>Bahnhofstrasse 15, Postfach 341, Zermatt CH- 3920, Switzerland<ph dataRef="d3" id="3" subType="xlf:lb" type="fmt"/>Tel: +44 203 468 2235  Email: <pc dataRefEnd="d5" dataRefStart="d4" id="4" type="link">info@elysiancollection.com</pc><sc dataRef="d1" id="5" subType="xlf:b" type="fmt"/><ph dataRef="d3" id="6" subType="xlf:lb" type="fmt"/><ec dataRef="d2" startRef="5" subType="xlf:b" type="fmt"/>',
                $units[4]['source']['raw-content'][0]
        );
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_xliff_v2(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/sample-20.xlf'));

        $exp = [
            'attr' =>
                [
                    'original'        => '389108a4-rtapi.xml',
                    'source-language' => 'en',
                    'target-language' => 'fr',
                ],
            'notes' =>
                [
                    0 => ['raw-content' => 'note for file.', ],
                    1 => ['raw-content' => 'note2 for file.',],
                    2 => ['json' => '{
                    "key": "value",
                    "key2": "value2",
                    "key3": "value3"
                }',
                    ],
                ],
            'trans-units' =>
                [
                    1 => [
                        'attr' => [
                            'id' => 'u1',
                            'translate' => 'yes',
                        ],
                        'notes' => [
                            0 => ['raw-content' => 'note for unit',],
                            1 => ['raw-content' => 'another note for unit.',],
                            2 => ['json' => '{
                        "key": "value",
                        "key2": "value2",
                        "key3": "value3"
                    }',
                            ],
                        ],
                         'source' => [
                             'raw-content' => [
                                 0 => '<pc id="1">Hello <mrk id="m1" type="term">World</mrk>!</pc>'
                             ],
                             'attr'    => [],
                         ],
                         'target' => [
                            'raw-content' => [
                                0 => '<pc id="1">Bonjour le <mrk id="m1" type="term">Monde</mrk> !</pc>',
                            ],
                             'attr'    => [],
                         ],
                        'seg-source' => [
                            0 => [
                                'mid' => 0,
                                'ext-prec-tags' => '<pc id="1">Hello ',
                                'raw-content' => 'World',
                                'ext-succ-tags' => '!</pc>;',
                            ]
                        ],
                        'seg-target' => [
                            0 => [
                                'mid' => 0,
                                'ext-prec-tags' => '<pc id="1">Bonjour le ',
                                'raw-content' => 'Monde',
                                'ext-succ-tags' => ' !</pc>',
                            ]
                        ]
                    ],
                    2 => [
                        'attr' => [
                            'id' => 'u2',
                        ],
                        'notes' => [
                            0 => [ 'raw-content' => 'note for unit2', ],
                            1 => [ 'raw-content' => 'another note for unit2.', ],
                            2 => [ 'json' => '{
                        "key": "value",
                        "key2": "value2",
                        "key3": "value3"
                    }',
                         ],
                    ],
                    'source' => [
                        'raw-content' => [
                            0 => '<pc id="2">Hello <mrk id="m2" type="term">World2</mrk>!</pc>',
                        ],
                        'attr'    => [],
                    ],
                    'target' => [
                        'raw-content' => [
                            0 => '<pc id="2">Bonjour le <mrk id="m2" type="term">Monde2</mrk> !</pc>',
                        ],
                        'attr'    => [],
                    ],
                    'seg-source' => [
                        0 => [
                            'mid' => 0,
                            'ext-prec-tags' => '<pc id="2">Hello ',
                            'raw-content' => 'World2',
                            'ext-succ-tags' => '!</pc>',
                        ]
                    ],
                    'seg-target' => [
                        0 => [
                            'mid' => 0,
                            'ext-prec-tags' => '<pc id="2">Bonjour le ',
                            'raw-content' => 'Monde2',
                            'ext-succ-tags' => ' !</pc>',
                        ]
                    ]
                ],
            ],
        ];

        $this->assertArraySimilar($parsed[ 'files' ][ 1 ], $exp);
    }

    /**
     */
    #[Test]
    public function raise_exception_on_duplicate_ids(): void
    {
        try {
            (new XliffParser())->xliffToArray($this->getTestFile('20/v2-duplicate-ids.xliff'));
        } catch (Exception $exception){
            $this->assertEquals('Invalid trans-unit id, duplicate found.', $exception->getMessage());
        }
    }

    /**
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_segment_state_attribute(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/with-segment-state.xliff'));

        $this->assertEquals('0', $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'seg-source' ][ 0 ][ 'attr' ][ 'id' ]);
        $this->assertEquals('initial', $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'seg-source' ][ 0 ][ 'attr' ][ 'state' ]);
        $this->assertEquals('4', $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'seg-source' ][ 1 ][ 'attr' ][ 'id' ]);
        $this->assertEquals('finale', $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'seg-source' ][ 1 ][ 'attr' ][ 'state' ]);
        $this->assertEquals('0', $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'seg-target' ][ 0 ][ 'attr' ][ 'id' ]);
        $this->assertEquals('initial', $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'seg-target' ][ 0 ][ 'attr' ][ 'state' ]);
        $this->assertEquals('4', $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'seg-target' ][ 1 ][ 'attr' ][ 'id' ]);
        $this->assertEquals('finale', $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'seg-target' ][ 1 ][ 'attr' ][ 'state' ]);
    }

    /**
     * Test parsing target with attributes (covers line 222)
     *
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_target_with_attributes(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/xliff20-target-with-attributes.xlf'));

        $this->assertArrayHasKey('attr', $parsed['files'][1]['trans-units'][1]['target']);
        $this->assertEquals('translated', $parsed['files'][1]['trans-units'][1]['target']['attr'][0]['state']);
    }

    /**
     * Test that missing unit id throws exception (covers line 268)
     *
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function throws_exception_for_missing_unit_id(): void
    {
        $this->expectException(NotFoundIdInTransUnit::class);
        $this->expectExceptionMessage('Invalid trans-unit id found. EMPTY value');

        (new XliffParser())->xliffToArray($this->getTestFile('20/xliff20-missing-unit-id.xlf'));
    }

    /**
     * Test that unit id too long throws exception (covers line 274)
     *
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function throws_exception_for_unit_id_too_long(): void
    {
        $this->expectException(SegmentIdTooLongException::class);
        $this->expectExceptionMessage('Segment-id too long. Max 100 characters allowed');

        (new XliffParser())->xliffToArray($this->getTestFile('20/xliff20-unit-id-too-long.xlf'));
    }

    /**
     * Test parsing originalData with JSON and placeholders (covers lines 331-335)
     *
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function can_parse_original_data_with_json_and_placeholders(): void
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('20/xliff20-original-data-with-json.xlf'));

        $this->assertArrayHasKey('original-data', $parsed['files'][1]['trans-units'][1]);
        $originalData = $parsed['files'][1]['trans-units'][1]['original-data'];
        $this->assertNotEmpty($originalData);
        // The JSON should have placeholders converted back to &lt; and &gt;
        $this->assertArrayHasKey('json', $originalData[0]);
        $this->assertStringContainsString('&lt;', $originalData[0]['json']);
        $this->assertStringContainsString('&gt;', $originalData[0]['json']);
    }

    /**
     * Test segment outside tGroup range logs warning (covers lines 425-426)
     *
     * @throws NotValidFileException
     * @throws NotSupportedVersionException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    #[Test]
    public function logs_warning_for_segment_outside_tgroup_range(): void
    {
        // Create a mock logger to capture the warning
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('is not included within tGroupBegin and tGroupEnd'));

        $parser = new XliffParser($logger);
        $parser->xliffToArray($this->getTestFile('20/xliff20-segment-outside-tgroup.xlf'));
    }
}
