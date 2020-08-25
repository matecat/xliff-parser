<?php

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\Exception\NotSupportedVersionException;
use Matecat\XliffParser\Exception\NotValidFileException;

class VersionDetector
{
    /**
     * @var array
     */
    private static $versions_1 = ['1.0', '1.1','1.2'];

    /**
     * @var array
     */
    private static $versions_2 = ['2.0'];

    /**
     * @param \DOMDocument $dom
     *
     * @return int
     * @throws NotValidFileException
     */
    public static function detect(\DOMDocument $dom)
    {
        var_dump($dom->getElementsByTagName('xliff')->item(0)->attributes->getNamedItem('version'));

        /** @var \DOMNode $xliff */
        foreach ($dom->getElementsByTagName('xliff') as $xliff) {
            $version = $xliff->attributes->getNamedItem('version');



            if ($version) {

                die();
                return self::resolveVersion($version->nodeValue);
            }

            $namespace = $xliff->attributes->getNamedItem('xmlns');
            if ($namespace) {
                if (0 !== substr_compare('urn:oasis:names:tc:xliff:document:', $namespace->nodeValue, 0, 34)) {
                    throw new \InvalidArgumentException(sprintf('Not a valid XLIFF namespace "%s".', $namespace));
                }

                return self::resolveVersion(substr($namespace, 34));
            }
        }

        // Falls back to v1.2
        return '1.2';

        // Not a valid XLIFF file, throw an exception
        throw new NotValidFileException('This is not a valid xliff file');
    }

    /**
     * @param string $version
     *
     * @return int
     */
    private static function resolveVersion($version)
    {
        if(in_array($version, self::$versions_1)){
            return 1;
        }

        if(in_array($version, self::$versions_2)){
            return 2;
        }
    }
}