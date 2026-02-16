<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

final class CheckGlobalSight implements CheckInterface
{

    /**
     * @inheritDoc
     */
    public function check(?array $tmp = []): ?array
    {
        if (isset($tmp[0]) && stripos($tmp[0], 'globalsight') !== false) {
            return [
                'proprietary' => true,
                'proprietary_name' => 'GlobalSight Download File',
                'proprietary_short_name' => 'globalsight',
                'converter_version' => 'legacy',
            ];
        }

        return null;
    }
}
