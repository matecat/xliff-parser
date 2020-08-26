# Xliff Parser

This library is a simple, agnostic Xliff parser specifically written for Matecat.

## Xliff Support

Xliff supported versions:

* [1.0](http://www.oasis-open.org/committees/xliff/documents/contribution-xliff-20010530.htm)
* [1.1](http://www.oasis-open.org/committees/xliff/documents/xliff-specification.htm)
* [1.2](http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html)
* [2.0](http://docs.oasis-open.org/xliff/xliff-core/v2.0/xliff-core-v2.0.html#data)

## Usage

In order to convert a xliff file into an array:

```php
//
use Matecat\XliffParser\XliffParser;

$parsed = XliffParser::toArray('your-file.xliff');
```

In case of invalid or not supported xliff file an empty array will be returned.

## Output skeleton

Since there are some differences between xliff v1 and v2, the output of the array will be slightly different:

| Parent element | Key             | V1 | V2 |
|----------------|-----------------|----|----|
| attr           | datatype        | *  |    |
| attr           | original        | *  | *  |
| attr           | source-language | *  | *  |
| attr           | target-language | *  | *  |
| notes          |                 |    | *  |
| trans-units    | alt-trans       | *  |    |
| trans-units    | attr            | *  | *  |
| trans-units    | context-group   | *  |    |
| trans-units    | locked          | *  |    |
| trans-units    | notes           | *  | *  |
| trans-units    | original-data   |    | *  |
| trans-units    | source          | *  | *  |
| trans-units    | target          | *  | *  |

## Examples

### xliff v1 (1.0, 1.1, 1.2) 

### xliff v2 (2.0) 

Input:

```xml
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0"
    version="2.0"
    srcLang="en"
    trgLang="fr"
    >
    <file id="f1" original="389108a4-rtapi.xml">
        <notes>
            <note id="n1">note for file.</note>
            <note id="n2">note2 for file.</note>
            <note id="n3">
                {
                    "key": "value",
                    "key2": "value2",
                    "key3": "value3"
                }
            </note>
        </notes>
        <unit id="u1" translate="test">
            <my:elem xmlns:my="myNamespaceURI" id="x1">data</my:elem>
            <notes>
                <note id="n1">note for unit</note>
                <note id="n2">another note for unit.</note>
                <note id="n3">
                    {
                        "key": "value",
                        "key2": "value2",
                        "key3": "value3"
                    }
                </note>
            </notes>
            <segment id="s1">
                <source>
                    <pc id="1">Hello <mrk id="m1" type="term">World</mrk>!</pc>
                </source>
                <target>
                    <pc id="1">Bonjour le <mrk id="m1" type="term">Monde</mrk> !</pc>
                </target>
            </segment>
        </unit>
        <unit id="u2">
            <my:elem xmlns:my="myNamespaceURI" id="x2">data</my:elem>
            <notes>
                <note id="n1">note for unit2</note>
                <note id="n2">another note for unit2.</note>
                <note id="n3">
                    {
                        "key": "value",
                        "key2": "value2",
                        "key3": "value3"
                    }
                </note>
            </notes>
            <segment id="s2">
                <source>
                    <pc id="2">Hello <mrk id="m2" type="term">World2</mrk>!</pc>
                </source>
                <target>
                    <pc id="2">Bonjour le <mrk id="m2" type="term">Monde2</mrk> !</pc>
                </target>
            </segment>
        </unit>
    </file>
</xliff>
```

Output:

```php

$output = [
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
                'original-data' => [],
                 'source' => [
                     'content' => '<pc id="1">Hello <mrk id="m1" type="term">World</mrk>!</pc>',
                     'attr'    => [],
                 ],
                 'target' => [
                    'content' => '<pc id="1">Bonjour le <mrk id="m1" type="term">Monde</mrk> !</pc>',
                     'attr'    => [],
                 ],
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
            'original-data' => [],
            'source' => [
                    'content' => '<pc id="2">Hello <mrk id="m2" type="term">World2</mrk>!</pc>',
                    'attr'    => [],
            ],
            'target' => [
                    'content' => '<pc id="2">Bonjour le <mrk id="m2" type="term">Monde2</mrk> !</pc>',
                    'attr'    => [],
            ],
        ],
    ],
];
```

## Support

If you found an issue or had an idea please refer [to this section](https://github.com/mauretto78/xliff-parser/issues).

## Authors

* **Mauro Cassani** - [github](https://github.com/mauretto78)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
