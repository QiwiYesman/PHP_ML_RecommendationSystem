<?php

require_once 'AprioriTraining.php';
require_once 'Eclat.php';
class EclatTraining extends AprioriTraining
{
    public function model(): Eclat
    {
        return  $this->model;
    }

    public function train(array $samples, int $support = 3, float $confidence = 0.3): void
    {
        $this->model = new Eclat($support, $confidence);
        $this->model->train($samples);
    }

    public function __construct()
    {
        parent::__construct();
        $this->cost_tableName = 'costs3';
    }
}