<?php

use Phpml\Association\Apriori;

require_once __DIR__.'/load_apriori.php';
require_once __DIR__.'/FPGrowthTraining.php';
class AprioriTraining extends PrepareTraining
{
    public function model(): Apriori|Eclat
    {
        return $this->model;
    }

    public function train(array $samples, int $support = 3, float $confidence = 0.3): void
    {
        $percentSupport=((float)$support)/count($samples);
        $this->model = new Apriori($percentSupport, $confidence);
        $labels = [];
        $this->model->train($samples, $labels);
    }

    public function __construct()
    {
        parent::__construct();
        $this->cost_tableName = 'costs2';
    }

    public function rulesToSets(array $rules): array
    {
        print_r($rules);
        $records = [];
        foreach ($rules as $rule)
        {
            $X = $rule['antecedent'];
            $Y = $rule['consequent'];
            $confidence = $rule['confidence'];
            foreach ($Y as $y)
            {
                $records[] =
                    [
                        "set"=>$X,
                        "main"=>$y,
                        "cost"=>$confidence
                    ];
            }
        }
        return $records;
    }

}