<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

final class CheckXliffVersion2 implements CheckInterface
{

    /**
     * @inheritDoc
     */
    public function check(?array $tmp = []): ?array
    {
        if (!isset($tmp[0])) {
            return null;
        }

        $content = substr($tmp[0], 0, 1000);
        preg_match('|<xliff.*?\sversion\s?=\s?["\'](.*?)["\']|si', $content, $versionMatches);
        preg_match(
            '|<xliff.*?\sxmlns\s?=\s?["\']urn:oasis:names:tc:xliff:document:(.*?)["\']|si',
            $content,
            $xmlnsMatches
        );

        if (empty($versionMatches) || empty($xmlnsMatches)) {
            return null;
        }

        $version = $versionMatches[1];
        $xmlns = $xmlnsMatches[1];

        return ($version === $xmlns && (float)$version >= 2) ? [
            'proprietary' => false,
            'proprietary_name' => 'Xliff v' . $version . ' File',
            'proprietary_short_name' => 'xliff_v2',
            'converter_version' => '2.0',
        ] : null;
    }
}
