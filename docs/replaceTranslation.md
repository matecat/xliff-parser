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
    public function thereAreErrors( $segment, $translation )
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
