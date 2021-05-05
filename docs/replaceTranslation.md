## replaceTranslation

### Usage

To replace a translation into a xliff file:

```php
//
use Matecat\XliffParser\XliffParser;

$parser = new XliffParser();
$parser->replaceTranslation($inputFile, $data, $transUnits, $targetLang, $outputFile, $callback);
```

Where:

* `$inputFile` is the input file full path
* `$data` is the data array
* `$transUnits` is the trans-unit id reverse map
* `$targetLang` is the target language
* `$outputFile` is the output file full path (if the file does not exist il will be created)
* `$callback` is an optional callback used to validate translations. It MUST implement `XliffReplacerCallbackInterface` and expose `thereAreErrors` method.

### Examples

Here is a full example:

```php
// ...

class DummyXliffReplacerCallback implements XliffReplacerCallbackInterface
{
    /**
     * @inheritDoc
     */
    public function thereAreErrors( $segment, $translation, array $dataRefMap = [] )
    {
        return false;
    }
}

$data = [
    [
        'sid' => 1,
        'segment' => '<pc id="1">Hello <mrk id="m2" type="term">World</mrk> !</pc>',
        'internal_id' => 'u1',
        'mrk_id' => '',
        'prev_tags' => '',
        'succ_tags' => '',
        'mrk_prev_tags' => '',
        'mrk_succ_tags' => '',
        'translation' => '<pc id="1">Buongiorno al <mrk id="m2" type="term">Mondo</mrk> !</pc>',
        'status' => TranslationStatus::STATUS_TRANSLATED,
        'eq_word_count' => 123,
        'raw_word_count' => 456,
    ],
    [
        'sid' => 2,
        'segment' => '<pc id="1">Hello <mrk id="m2" type="term">World2</mrk> !</pc>',
        'internal_id' => 'u2',
        'mrk_id' => '',
        'prev_tags' => '',
        'succ_tags' => '',
        'mrk_prev_tags' => '',
        'mrk_succ_tags' => '',
        'translation' => '<pc id="2">Buongiorno al <mrk id="m2" type="term">Mondo2</mrk> !</pc>',
        'status' => TranslationStatus::STATUS_TRANSLATED,
        'eq_word_count' => 54353,
        'raw_word_count' => 54354,
    ],
];

$transUnits = [];

foreach ( $data as $i => $k ) {
    //create a secondary indexing mechanism on segments' array; this will be useful
    //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
    $internalId = $k[ 'internal_id' ];

    $transUnits[ $internalId ] [] = $i;

    $data[ 'matecat|' . $internalId ] [] = $i;
}

$inputFile = __DIR__.'/../tests/files/sample-20.xlf';
$outputFile = __DIR__.'/../tests/files/output/sample-20.xlf';

$parser = new XliffParser();
$parser->replaceTranslation( $inputFile, $data, $transUnits, 'fr-fr', $outputFile, new DummyXliffReplacerCallback() );
```

### Missing `target` node in the original file

`XliffParser` is capable to create a `target` node if this is missing in the original xliff file.
  
Please note that the `<target>` will be placed just BEFORE its corresponding closing `segment`.

Take a look at this example, this is the original `unit`:

```xml
<unit id="tu1">
    <segment>
        <source xml:space="preserve">Titolo del documento</source>
    </segment>
</unit>
```

And this is the corresponding `unit` in the replaced file:

```xml
<unit help-id="1" id="tu1">
   <segment>
    <source xml:space="preserve">Titolo del documento</source>
   <target>Document title</target></segment>
            <mda:metadata>
                <mda:metagroup category="row_xml_attribute">
                    <mda:meta type="x-matecat-raw">0</mda:meta>
                    <mda:meta type="x-matecat-weighted">0</mda:meta>
                </mda:metagroup>
            </mda:metadata>
  </unit>
```

### `translate` attribute

Translations will be not replaced in trans-units when `translate` attribute set to `no`.

Consider this `trans-unit` taken from a classic xliff v1.2 file:

```xml
<trans-unit help-id="1" id="1" restype="x-xhtml-madcap:keyword-term" phase-name="pretrans" translate="no">
	<source>Tools:Review</source>
	<seg-source>
		<mrk mtype="seg" mid="1">Tools:Review</mrk>
	</seg-source>
	<target state="needs-translation">
		<mrk mtype="seg" mid="1" MadCap:segmentStatus="Untranslated" MadCap:matchPercent="0"/>
	</target>
</trans-unit>
```

In this case the replacer will do not touch `target` and it is simply left as is.