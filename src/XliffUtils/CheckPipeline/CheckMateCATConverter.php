<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

final class CheckMateCATConverter implements CheckInterface
{

    /**
     * @inheritDoc
     */
    public function check(?array $tmp = []): ?array
    {
        if (!isset($tmp[0])) {
            return null;
        }

        preg_match('#tool-id\s*=\s*"matecat-converter(\s+([^"]+))?"#i', $tmp[0], $matches);

        if (empty($matches)) {
            return null;
        }

        return [
            'proprietary' => false,
            'proprietary_name' => 'MateCAT Converter',
            'proprietary_short_name' => 'matecat_converter',
            // First converter release didn't specify version
            'converter_version' => $matches[2] ?? '1.0',
        ];
    }
}
