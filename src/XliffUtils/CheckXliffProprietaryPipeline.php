<?php

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\XliffUtils\CheckPipeline\CheckInterface;

class CheckXliffProprietaryPipeline {
    /**
     * @var array|null
     */
    private ?array $tmp;

    /**
     * @var array|null
     */
    private ?array $steps;

    /**
     * CheckXliffProprietaryPipeline constructor.
     *
     * @param array|null $tmp
     */
    public function __construct( ?array $tmp = [] ) {
        $this->tmp   = $tmp;
        $this->steps = [];
    }

    /**
     * @param CheckInterface $step
     */
    public function addCheck( CheckInterface $step ) {
        $this->steps[] = $step;
    }

    /**
     * @return array
     */
    public function run(): array {
        $fileType = [];

        /** @var CheckInterface $step */
        foreach ( $this->steps as $step ) {
            if ( null !== $step->check( $this->tmp ) ) {
                $fileType = $step->check( $this->tmp );
            }
        }

        if ( !empty( $fileType ) && $this->isValid( $fileType ) ) {
            return $fileType;
        }

        return [
                'proprietary'            => false,
                'proprietary_name'       => null,
                'proprietary_short_name' => null,
                'converter_version'      => null,
        ];
    }

    /**
     * @param $fileType
     *
     * @return bool
     */
    private function isValid( $fileType ): bool {
        $mandatoryKeys = [
                'proprietary',
                'proprietary_name',
                'proprietary_short_name',
                'converter_version',
        ];

        return array_keys( $fileType ) === $mandatoryKeys;
    }
}
