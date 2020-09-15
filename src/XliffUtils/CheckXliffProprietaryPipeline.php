<?php

namespace Matecat\XliffParser\XliffUtils;

use Matecat\XliffParser\XliffUtils\Pipeline\CheckInterface;

class CheckXliffProprietaryPipeline
{
    /**
     * @var string
     */
    private $tmp;

    /**
     * @var array
     */
    private $steps;

    /**
     * CheckXliffProprietaryPipeline constructor.
     *
     * @param $tmp
     */
    public function __construct($tmp)
    {
        $this->tmp = $tmp;
        $this->steps = [];
    }

    /**
     * @param CheckInterface $step
     */
    public function addCheck( CheckInterface $step)
    {
        $this->steps[] = $step;
    }

    /**
     * @return array
     */
    public function run()
    {
        $fileType = [];

        $mandatoryKeys = ['proprietary', 'proprietary_name' , 'proprietary_short_name' , 'converter_version', ];

        /** @var CheckInterface $step */
        foreach ($this->steps as $step){
            $fileType = $step->check($this->tmp);
        }

        if(!empty($fileType) and array_keys($fileType) === $mandatoryKeys ){
            return $fileType;
        }

        return [
            'proprietary' => false,
            'proprietary_name' => null,
            'proprietary_short_name' => null,
            'converter_version' => null,
        ];
    }
}