<?php
require_once '../RuleExtension.php';


//fpgrowth-set extending
$ruler = new RuleExtension();
$ruler->switchTableMyMethod(TrainMethods::FPGrowth);
$ruler->train(1000, 0.3);
$ruler->saveCosts($ruler->new_rules);
print_r($ruler->new_rules);

//apriori-set extending
$ruler = new RuleExtension();
$ruler->switchTableMyMethod(TrainMethods::Apriori);
$ruler->train(1000, 0.3);
$ruler->saveCosts($ruler->new_rules);
print_r($ruler->new_rules);

//eclat-set extending
$ruler = new RuleExtension();
$ruler->switchTableMyMethod(TrainMethods::Eclat);
$ruler->train(1000, 0.3);
$ruler->saveCosts($ruler->new_rules);
print_r($ruler->new_rules);