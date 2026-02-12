# Xliff Parser

[![license](https://img.shields.io/github/license/matecat/xliff-parser.svg)]()
[![Packagist](https://img.shields.io/packagist/v/matecat/xliff-parser.svg)]()
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=matecat_xliff-parser&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=matecat_xliff-parser)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=matecat_xliff-parser&metric=coverage)](https://sonarcloud.io/summary/new_code?id=matecat_xliff-parser)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=matecat_xliff-parser&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=matecat_xliff-parser)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=matecat_xliff-parser&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=matecat_xliff-parser)

This library is a simple, agnostic Xliff parser specifically written for [Matecat](https://www.matecat.com).

## Installation

```shell
composer require matecat/xliff-parser
```

## Xliff Support

Xliff supported versions:

* [1.0](http://www.oasis-open.org/committees/xliff/documents/contribution-xliff-20010530.htm)
* [1.1](http://www.oasis-open.org/committees/xliff/documents/xliff-specification.htm)
* [1.2](http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html)
* [2.0](http://docs.oasis-open.org/xliff/xliff-core/v2.0/xliff-core-v2.0.html#data)

## Methods

* [xliffToArray](https://github.com/matecat/xliff-parser/blob/master/docs/xliffToArray.md) - converts a xliff file into an array
* [replaceTranslation](https://github.com/matecat/xliff-parser/blob/master/docs/replaceTranslation.md) - replace a translation in a xliff file 

## Logging

You can inject your own logger (must be a `LoggerInterface` implementation):

```php
// ...

// $logger must be implement PS3 LoggerInterface
$parser = new XliffParser($logger);

```

## Support

If you found an issue or had an idea please refer [to this section](https://github.com/mauretto78/xliff-parser/issues).

## Authors

* **Domenico Lupinetti** - [github](https://github.com/ostico)
* **Mauro Cassani** - [github](https://github.com/mauretto78)

## License

This project is licensed under the GNU Lesser General Public License v3.0 or later. - see the [LICENSE.md](LICENSE.md) file for details
