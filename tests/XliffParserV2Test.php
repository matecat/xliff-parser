<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;

class XliffParserV2Test extends BaseTest
{
    /**
     * @test
     */
    public function can_parse_xliff_v2_with_char_limit()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('char-limit.xliff'));
        $attr  = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ][ 'attr' ];

        $this->assertEquals($attr['sizeRestriction'], 55);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_with_encoded_g_tags_in_originalData()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('xliff_20_with_g_tags_in_dataref.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $originalData = $units[1]['original-data'][0];

        $this->assertEquals($originalData['raw-content'], '&lt;g id="0PEY7rmSqeVk51Xn" ctype="x-html-strong" /&gt;');
        $this->assertEquals($originalData['attr']['id'], 'd1');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_with_new_line_values_in_originalData()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('blank-dataRef.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertArrayHasKey('original-data', $units[4]);
        $this->assertEquals($units[4]['original-data'][0]['raw-content'], '  ');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_with_pc_tags()
    {
        // <pc> tags do not be escaped here
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('1111_prova.md.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals('Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.', $units[2][ 'source' ]['raw-content'][0]);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_with_double_encoded_map()
    {
        // &amp;#39;
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber/39.xliff.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals("&lt;p class=&quot;cmln__paragraph&quot;&gt;", $units[4][ 'original-data' ][1]['raw-content']);
        $this->assertEquals("&#39;", $units[4][ 'original-data' ][2]['raw-content']);
        $this->assertEquals("&lt;/p&gt;", $units[4][ 'original-data' ][0]['raw-content']);

        // &amp;amp;
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber/&.xliff.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals("&amp;", $units[2][ 'original-data' ][7]['raw-content']);

        // &amp;apos;
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber/apos.xliff.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals("&apos;", $units[5][ 'original-data' ][0]['raw-content']);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_metadata()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber-v2.xliff'));
        $attr   = $parsed[ 'files' ][ 1 ][ 'attr' ];

        $this->assertCount(3, $attr);
        $this->assertEquals($attr[ 'source-language' ], 'en-us');
        $this->assertEquals($attr[ 'target-language' ], 'el-gr');
        $this->assertEquals($attr[ 'original' ], '389108a4-rtapi.xml');
        $this->assertEmpty($parsed[ 'files' ][ 1 ][ 'notes' ]);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_notes()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('sample-20.xlf'));
        $notes  = $parsed[ 'files' ][ 1 ][ 'notes' ];

        $this->assertCount(3, $notes);
        $this->assertEquals($notes[ 0 ][ 'raw-content' ], 'note for file.');
        $this->assertEquals($notes[ 1 ][ 'raw-content' ], 'note2 for file.');
        $this->assertEquals($notes[ 2 ][ 'json' ], '{
                    "key": "value",
                    "key2": "value2",
                    "key3": "value3"
                }');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_metadata()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('sample-20.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertCount(2, $units);
        $this->assertEquals($units[ 1 ][ 'attr' ][ 'id' ], 'u1');
        $this->assertEquals($units[ 1 ][ 'attr' ][ 'translate' ], 'test');
        $this->assertEquals($units[ 2 ][ 'attr' ][ 'id' ], 'u2');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_originalData()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber-v2.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertEquals($units[ 5 ][ 'original-data' ][ 0 ][ 'raw-content' ], '${redemptionLimit}');
        $this->assertEquals($units[ 5 ][ 'original-data' ][ 0 ][ 'attr' ][ 'id' ], 'source1');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_notes()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('uber-v2.xliff'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];
        $note   = $units[ 1 ][ 'notes' ];

        $this->assertEquals($note[ 0 ][ 'raw-content' ], 'note for unit');
        $this->assertEquals($note[ 1 ][ 'raw-content' ], 'another note for file.');
        $this->assertEquals($note[ 2 ], [
                'raw-content' => '01d35857-b9bd-4835-8db1-40febcdcc8e9',
                'attr'        => [
                        'type' => 'key'
                ]
        ]);
        $this->assertEquals($note[ 3 ], [
                'raw-content' => 'Repo: &lt;a href ="https://i18n.uberinternal.com/rosetta2/repo/rtapi/keys" target="_blank"&gt;rtapi&lt;/a&gt; Key Name: &lt;a href="https://i18n.uberinternal.com/rosetta2/repo/rtapi/key/driver_tasks.delivery_reminders.order.wda.title/overview" target="_blank"&gt;driver_tasks.delivery_reminders.order.wda.title&lt;/a&gt; Description: &lt;font color="blue"&gt;Title for delivery reminders when an eater has changed the dropoff location for an order&lt;/font&gt;',
                'attr'        => [
                        'type' => 'key-note'
                ]
        ]);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_source_and_target()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('sample-20.xlf'));

        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];
        $this->assertCount(2, $units);

        $this->assertStringContainsString('<pc id="1">Hello <mrk id="m1" type="term">World</mrk>!</pc>', $units[ 1 ][ 'source' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('<pc id="2">Hello <mrk id="m2" type="term">World2</mrk>!</pc>', $units[ 2 ][ 'source' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('<pc id="1">Bonjour le <mrk id="m1" type="term">Monde</mrk> !</pc>', $units[ 1 ][ 'target' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('<pc id="2">Bonjour le <mrk id="m2" type="term">Monde2</mrk> !</pc>', $units[ 2 ][ 'target' ][ 'raw-content' ][0]);
        $this->assertEquals($units[ 1 ][ 'source' ][ 'attr' ], []);
        $this->assertEquals($units[ 2 ][ 'source' ][ 'attr' ], []);
        $this->assertEquals($units[ 1 ][ 'target' ][ 'attr' ], []);
        $this->assertEquals($units[ 2 ][ 'target' ][ 'attr' ], []);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_nested_in_groups()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('sample-20-with-group.xlf'));

        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];
        $this->assertCount(3, $units);
        $this->assertStringContainsString('Sentence from a group', $units[ 1 ][ 'source' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Phrase from a group', $units[ 1 ][ 'target' ][ 'raw-content' ][0]);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_with_ti_in_nested_groups()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('sample-20-with-nested-group.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertStringContainsString('Sentence 1. ', $units[ 1 ][ 'source' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Phrase 1. ', $units[ 1 ][ 'target' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Sentence 2. ', $units[ 2 ][ 'source' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Phrase 2. ', $units[ 2 ][ 'target' ][ 'raw-content' ][0]);
        $this->assertStringContainsString('Sentence 3. ', $units[ 2 ][ 'source' ][ 'raw-content' ][1]);
        $this->assertStringContainsString('Phrase 3. ', $units[ 2 ][ 'target' ][ 'raw-content' ][1]);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_segmented_source_and_target()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('sample-20-segmented.xlf'));

        $source  = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ]['source'];
        $target  = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ]['target'];

        $this->assertEquals($source['raw-content'][0], 'Sentence 1. ');
        $this->assertEquals($target['raw-content'][0], 'Phrase 1. ');
        $this->assertEquals($source['raw-content'][1], 'Sentence 2. ');
        $this->assertEquals($target['raw-content'][1], 'Phrase 2. ');
        $this->assertEquals($source['raw-content'][2], 'Sentence 3. ');
        $this->assertEquals($target['raw-content'][2], 'Phrase 3. ');
        $this->assertEquals($source['raw-content'][3], '<pc id="1">pc</pc> Sentence 4.');
        $this->assertEquals($target['raw-content'][3], 'Phrase 4.');

        $this->assertEquals($source['attr'][ 3 ], [
            'xml:space' => 'space',
            'xml:lang' => 'fr',
        ]);
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_trans_units_segmented_seg_source_and_seg_target()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('sample-20-segmented.xlf'));

        $segSource  = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ]['seg-source'];
        $segTarget  = $parsed[ 'files' ][ 1 ][ 'trans-units' ][ 1 ]['seg-target'];

        $this->assertEquals($segSource[0]['raw-content'], 'Sentence 1. ');
        $this->assertEquals($segTarget[0]['raw-content'], 'Phrase 1. ');
        $this->assertEquals($segSource[1]['raw-content'], 'Sentence 2. ');
        $this->assertEquals($segTarget[1]['raw-content'], 'Phrase 2. ');
        $this->assertEquals($segSource[2]['raw-content'], 'Sentence 3. ');
        $this->assertEquals($segTarget[2]['raw-content'], 'Phrase 3. ');
        $this->assertEquals($segSource[3]['raw-content'], '<pc id="1">pc</pc> Sentence 4.');
        $this->assertEquals($segTarget[3]['raw-content'], 'Phrase 4.');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2_with_ec_and_sc()
    {
        // <pc> tags do not be escaped here
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('pcec.xlf'));
        $units  = $parsed[ 'files' ][ 1 ][ 'trans-units' ];

        $this->assertArrayHasKey('source', $units[4]);
        $this->assertEquals($units[4]['source']['raw-content'][0], '
                    <sc dataRef="d1" id="1" subType="xlf:b" type="fmt"/>Elysian Collection<ph dataRef="d3" id="2" subType="xlf:lb" type="fmt"/><ec dataRef="d2" startRef="1" subType="xlf:b" type="fmt"/>Bahnhofstrasse 15, Postfach 341, Zermatt CH- 3920, Switzerland<ph dataRef="d3" id="3" subType="xlf:lb" type="fmt"/>Tel: +44 203 468 2235  Email: <pc dataRefEnd="d5" dataRefStart="d4" id="4" type="link">info@elysiancollection.com</pc><sc dataRef="d1" id="5" subType="xlf:b" type="fmt"/><ph dataRef="d3" id="6" subType="xlf:lb" type="fmt"/><ec dataRef="d2" startRef="5" subType="xlf:b" type="fmt"/>');
    }

    /**
     * @test
     */
    public function can_parse_xliff_v2()
    {
        $parsed = (new XliffParser())->xliffToArray($this->getTestFile('sample-20.xlf'));

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
                            'translate' => 'test',
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
}
