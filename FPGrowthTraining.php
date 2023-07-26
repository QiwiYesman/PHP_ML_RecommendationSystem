<?php

use lixam\PhpFPGrowth\FPGrowth;

require_once 'load_fp.php';
require_once 'PrepareTraining.php';

/**
 * Using algorithm FPGrowth to find rules
 */
class FPGrowthTraining extends PrepareTraining
{
    public function model(): FPGrowth
    {
        return $this->model;
    }

    public function train(array $samples, int $support = 3, float $confidence = 0.3): void
    {
        $this->model = new FPGrowth($support, $confidence);
        $this->model->run($samples);
    }

    public function rulesToSets(array $rules): array
    {
        $record = [];
        foreach ($rules as $rule)
        {
            $X = explode(",", $rule[0]);
            $Y = explode(",",$rule[1]);
            $confidence = $rule[2];
            foreach ($Y as $y)
            {
                $record[] =
                    [
                        "set"=>$X,
                        "main"=>$y,
                        "cost"=>$confidence
                    ];
            }
        }
        return $record;
    }
    public function __construct()
    {
        parent::__construct();
        $this->cost_tableName = 'costs';
    }
}