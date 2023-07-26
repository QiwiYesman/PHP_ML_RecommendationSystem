<?php
require_once '../RecommendSystem.php';

$system = new RecommendSystem();


//apriori, with top, no limits

$system->switchTableMyMethod(TrainMethods::Apriori);
print_r($system->recommendByTop(20, 100, 0.3));

//fpgrowth, without top, 5 limit

$system->switchTableMyMethod(TrainMethods::FPGrowth);
print_r($system->limit(
    $system->recommendByTopWithoutTop(20, 100, 0.3),
    5));


//recommend by all table, without top, no limit
print_r(
    $system->recommendByTopFromAllTablesWithoutTop(20, 100, 0.3));