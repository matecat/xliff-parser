<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffUtils\CheckPipeline;

final class CheckSDL implements CheckInterface
{

    /**
     * @inheritDoc
     */
    public function check(?array $tmp = []): ?array
    {
        if (isset($tmp[0]) && stripos($tmp[0], 'sdl:version') !== false) {
            // Little trick: we consider SDL xliff files as not proprietary because we can handle them
            return [
                'proprietary' => false,
                'proprietary_name' => 'SDL Studio ',
                'proprietary_short_name' => 'trados',
                'converter_version' => 'legacy',
            ];
        }

        return null;
    }
}
