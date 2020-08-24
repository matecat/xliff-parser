# WholeTextFinder

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/b9c1cf5c3f014285b5d7748cb4175c69)](https://www.codacy.com/app/mauretto78_2/whole-text-finder?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=mauretto78/whole-text-finder&amp;utm_campaign=Badge_Grade)
[![license](https://img.shields.io/github/license/matecat/whole-text-finder.svg)]()
[![Packagist](https://img.shields.io/packagist/v/matecat/whole-text-finder.svg)]()

**WholeTextFinder** is a simple whole text finder.

## Basic Usage

Use the `find` method. Here is a basic search:

```php
//..
use Finder\WholeTextFinder;

$haystack  = "PHP PHP is the #1 web scripting PHP language of choice.";
$needle = "php";

// 3 matches
$matches = WholeTextFinder::find($haystack, $needle);

// $matches is equals to:
//
// array(3) {
//    [0] =>
//  array(2) {
//            [0] =>
//    string(3) "PHP"
//            [1] =>
//    int(0)
//  }
//  [1] =>
//  array(2) {
//            [0] =>
//    string(3) "PHP"
//            [1] =>
//    int(4)
//  }
//  [2] =>
//  array(2) {
//            [0] =>
//    string(3) "PHP"
//            [1] =>
//    int(32)
//  }
// }

```

## Find and Replace

There is also available a `findAndReplace` method:

```php
//..
use Finder\WholeTextFinder;

$haystack = 'Δύο παράγοντες καθόρισαν την αντίληψή μου για την Τενεσί Ουίλιαμς και τη σκηνική παρουσίαση των κειμένων: η Maria Britneva και η Annette Saddik, αφετέρου.';
$needle = 'και';
$replacement = 'test';

$matches = WholeTextFinder::findAndReplace($haystack, $needle, $replacement);

// $matches is equals to:
//
// array(2) {
//   ["replacement"]=>
//   string(252) "Δύο παράγοντες καθόρισαν την αντίληψή μου για την Τενεσί Ουίλιαμς test τη σκηνική παρουσίαση των κειμένων: η Maria Britneva test η Annette Saddik, αφετέρου."
//   ["occurrencies"]=>
//   array(2) {
//     [0]=>
//     array(2) {
//       [0]=>
//       string(6) "και"
//       [1]=>
//       int(122)
//     }
//     [1]=>
//     array(2) {
//       [0]=>
//       string(6) "και"
//       [1]=>
//       int(213)
//     }
//   }
// } 
//

```

This method will automatically **exclude** from replace the following tags:

#### HTML tags

- `a`
- `abbr`
- `address`
- `area`
- `article`
- `aside`
- `audio`
- `b`
- `base`
- `bdi`
- `bdo`
- `blockquote`
- `body`
- `button`
- `bx`
- `canvas`
- `caption`
- `cite`
- `code`
- `col`
- `colgroup`
- `data`
- `datalist`
- `dd`
- `del`
- `details`
- `dfn`
- `dialog`
- `div`
- `dl`
- `dt`
- `em`
- `embed`
- `ex`
- `fieldset`
- `figure`
- `footer`
- `form`
- `g`
- `h1`
- `h2`
- `h3`
- `h4`
- `h5`
- `h6`
- `head`
- `header`
- `hgroup`
- `hr`
- `html`
- `i`
- `iframe`
- `input`
- `ins`
- `kbd`
- `keygen`
- `label`
- `legend`
- `li`
- `link`
- `main`
- `map`
- `mark`
- `menu`
- `menuitem`
- `meta`
- `meter`
- `nav`
- `noscript`
- `object`
- `ol`
- `optgroup`
- `option`
- `output`
- `p`
- `param`
- `ph`
- `pre`
- `q`
- `rb`
- `rp`
- `rt`
- `rtc`
- `ruby`
- `s`
- `samp`
- `script`
- `section`
- `select`
- `small`
- `source`
- `span`
- `strong`
- `style`
- `sub`
- `summary`
- `sup`
- `table`
- `tbody`
- `td`
- `template`
- `textarea`
- `tfoot`
- `th`
- `thead`
- `time`
- `title`
- `tr`
- `track`
- `u`
- `ul`
- `var`
- `video`
- `wbr`

#### HTML self-closing tags

- `br`
- `bx`
- `ex`
- `hr`
- `img`

#### Matecat special tags

- `||||`
- `%{\w*}`

So, for example:

```php
//..
use Finder\WholeTextFinder;

$haystack = "Beauty -> 2 Anti-Akne Gesichtsreiniger Schlankmacher <g id=\"2\">XXX</g>";
$needle = 2;
$replacement = "test";

$matches = WholeTextFinder::findAndReplace($haystack, $needle, $replacement);

// $matches is equals to:
//
// array(2) {
//   ["replacement"]=>
//   string(252) "Beauty -> test Anti-Akne Gesichtsreiniger Schlankmacher <g id="2">XXX</g>"
//   ["occurrencies"]=>
//   array(1) {
//    [0]=>
//      array(2) {
//        [0]=>
//        string(1) "2"
//        [1]=>
//        int(10)
//     }
//   }
// } 
//

```

## Search options

Some options are avaliable:

You can also specify four options:

* **$skipHtmlEntities** (`true` by default)  
* **$exactMatch** (`false` by default) 
* **$caseSensitive** (`false` by default) 
* **$preserveNbsps** (`false` by default) 

Here are some examples:

```php
//..
use Finder\WholeTextFinder;

$haystack  = "PHP PHP is the #1 web scripting PHP language of choice.";

// 0 matches
$needle = "php";
$matches = WholeTextFinder::find($haystack, $needle, true, true, true);
   
// 1 match 
$needle = "#1";
$matches = WholeTextFinder::find($haystack, $needle, true, true, true);

// 1 match, even if the haystack contains an invisible nbsp and the needle has an ordinary spacer
$haystackWithNbsp  = "Lawful basis for processing including basis of legitimate interest";
$needleWithoutNbsp = "Lawful basis for processing including basis of legitimate interest";
$matches = WholeTextFinder::find($haystackWithNbsp, $needleWithoutNbsp, true, true, true);
   
```

## Support

If you found an issue or had an idea please refer [to this section](https://github.com/mauretto78/whole-text-finder/issues).

## Authors

* **Mauro Cassani** - [github](https://github.com/mauretto78)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
