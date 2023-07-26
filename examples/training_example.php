<?php
require_once '../AprioriTraining.php';
require_once '../EclatTraining.php';
require_once '../FPGrowthTraining.php';


$transactions = [
    ['a', 'b', 'c'],
    ['c', 'b', 'a'],
    ['a', 'c'],
    ['b', 'd'],
    ['b', 'c', 'd'],
    ['b', 'k', 'c']
];
$minsup = 2;


$apriori = new AprioriTraining();
$apriori->train($transactions, $minsup, 0.3);
print_r($apriori->model()->getRules());

$eclat = new EclatTraining();
$eclat->train($transactions, $minsup, 0.3);
print_r($eclat->model()->getRules());

$fp = new FPGrowthTraining();
$fp->train($transactions, $minsup, 0.3);
print_r($fp->model()->getRules());


