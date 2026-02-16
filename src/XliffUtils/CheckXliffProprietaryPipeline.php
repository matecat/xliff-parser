<?php

declare(strict_types=1);

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckInterface;

final class CheckXliffProprietaryPipeline
{

    /** @var array<int, string>|null */
    private ?array $tmp;

    /** @var array<int, CheckInterface> */
    private array $steps = [];

    /**
     * @param array<int, string>|null $tmp First 1024 chars of XLIFF content
     */
    public function __construct(?array $tmp = [])
    {
        $this->tmp = $tmp;
    }

    /**
     * Add a check step to the pipeline.
     */
    public function addCheck(CheckInterface $step): void
    {
        $this->steps[] = $step;
    }

    /**
     * Run all checks and return file type information.
     *
     * @return array{proprietary: bool, proprietary_name: string|null, proprietary_short_name: string|null, converter_version: string|null}
     */
    public function run(): array
    {
        foreach ($this->steps as $step) {
            $result = $step->check($this->tmp);
            if ($result !== null && $this->isValid($result)) {
                return $result;
            }
        }

        return [
            'proprietary' => false,
            'proprietary_name' => null,
            'proprietary_short_name' => null,
            'converter_version' => null,
        ];
    }

    /**
     * Validate that file type array has all required keys.
     *
     * @param array<string, mixed> $fileType
     */
    private function isValid(array $fileType): bool
    {
        $mandatoryKeys = [
            'proprietary',
            'proprietary_name',
            'proprietary_short_name',
            'converter_version',
        ];

        return array_keys($fileType) === $mandatoryKeys;
    }
}
