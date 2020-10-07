## dataRef replacement

Xliff 2.0 standard supports `originalData` to map special data in source and target.

### `<ph>`,`<sc>` and `<ec>` tags

These three tag use `dataRef` attribute to link content from `originalData`. Take a look at the example:

```xml
<?xml version='1.0' encoding='UTF-8'?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" xmlns:mda="urn:oasis:names:tc:xliff:metadata:2.0" xmlns:slr="urn:oasis:names:tc:xliff:sizerestriction:2.0" xmlns:memsource="http://www.memsource.com/xliff2.0/1.0" version="2.0" memsource:wfLevel="1" srcLang="en-us" trgLang="bn-bd">
    <file id="0MbMo42dByuvf0mv1_dc6:0-0" memsource:taskId="0MbMo42dByuvf0mv1_dc6" canResegment="no" original="7cf155ce-rtapi.xml">
        <slr:profiles generalProfile="xliff:codepoints"/>
        <unit id="0">
            <originalData>
                <data id="source1">${AMOUNT}</data>
                <data id="source2">${RIDER}</data>
            </originalData>
            <segment id="0" state="initial">
                <source>Did you collect <ph id="source1" dataRef="source1"/> from <ph id="source2" dataRef="source2"/>?</source>
                <target></target>
            </segment>
        </unit>
    </file>
</xliff>
```

`Matecat\XliffParser\XliffUtils\DataRefReplace` class is capable to introduce a `equiv-text` (with the base64 encoded corresponding value) within `<ph>`,`<sc>` and `<ec>` tags:

```php

// ...
use Matecat\XliffParser\XliffUtils\DataRefReplacer;

// provide original data map
$map = [
    'source1' => '${AMOUNT}',
    'source2' => '${RIDER}',
];

$dataReplacer = new DataRefReplacer($map);

$string = 'Did you collect <ph id="source1" dataRef="source1"/> from <ph id="source2" dataRef="source2"/>?';

$replaced = $dataReplacer->replace($string);

// $replaced is:
// Did you collect <ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/> from <ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/>?

```

### `<pc>` tag

This tag uses `dataRefStart` and `dataRefEnd` attributes.

```xml
<?xml version="1.0"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en-US" trgLang="it-IT" xmlns:its="http://www.w3.org/2005/11/its" xmlns:itsxlf="http://www.w3.org/ns/its-xliff/" its:version="2.0">
    <file id="f1" original="/home/afalappa/Documenti/filters-sample-docs/markdown/prova.md">
        <unit id="tu1">
            <segment>
                <source xml:space="preserve">Titolo del documento</source>
            </segment>
        </unit>
        <unit id="tu2">
            <originalData>
                <data id="d1">_</data>
                <data id="d2">**</data>
                <data id="d3">`</data>
            </originalData>
            <segment>
                <source xml:space="preserve">Testo libero contenente <pc id="3" dataRefEnd="d1" dataRefStart="d1"><pc id="4" dataRefEnd="d2" dataRefStart="d2">grassetto + corsivo</pc></pc></source>
            </segment>
        </unit>
    </file>
</xliff>
```

In this case `DataRefReplacer` replaces `<pc>` tags with fictional `<ph>` tags to be consumed by Matecat UI. As `<pc>` are classic opening-closing tags, its id like `1` is splitted in `1_1` and
 `1_2` in the two new `<ph>` tags. Take a look at the example below:

```php

// ...
use Matecat\XliffParser\XliffUtils\DataRefReplacer;

$map = [
    'd1' => '_',
    'd2' => '**',
    'd3' => '`',
];

$dataReplacer = new DataRefReplacer($map);

$string = 'Testo libero contenente <pc id="3" dataRefEnd="d1" dataRefStart="d1"><pc id="4" dataRefEnd="d2" dataRefStart="d2">grassetto + corsivo</pc></pc>';

$replaced = $dataReplacer->replace($string);

// $replaced is:
// Testo libero contenente <ph id="3_1" dataType="pcStart" originalData="PHBjIGlkPSIzIiBkYXRhUmVmRW5kPSJkMSIgZGF0YVJlZlN0YXJ0PSJkMSI+" dataRef="d1" equiv-text="base64:Xw=="/><ph id="4_1" dataType="pcStart" originalData="PHBjIGlkPSI0IiBkYXRhUmVmRW5kPSJkMiIgZGF0YVJlZlN0YXJ0PSJkMiI+" dataRef="d2" equiv-text="base64:Kio="/>grassetto + corsivo<ph id="4_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d2" equiv-text="base64:Kio="/><ph id="3_2" dataType="pcEnd" originalData="PC9wYz4=" dataRef="d1" equiv-text="base64:Xw=="/>

```

In this case a special `originalData` attribute is appended to each `<ph>` generated tag to restore original `<pc>` tag (see below).

## Restoring original content

`DataRefReplacer` is also capable to restore original content:

```php

//...
use Matecat\XliffParser\XliffUtils\DataRefReplacer;

// provide original data map
$map = [
    'source1' => '${AMOUNT}',
    'source2' => '${RIDER}',
];

$dataReplacer = new DataRefReplacer($map);

$string = 'Did you collect <ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/> from <ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/>?';

$replaced =$dataReplacer->restore($string);

// $replaced is:
// Did you collect <ph id="source1" dataRef="source1"/> from <ph id="source2" dataRef="source2"/>?

```
