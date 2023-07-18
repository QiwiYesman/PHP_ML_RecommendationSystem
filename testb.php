<?php

require_once ("./phpml/vendor/autoload.php");
require_once ('OrderList.php');
use Phpml\Classification\KNearestNeighbors;
use Phpml\ModelManager;
$read = json_decode(file_get_contents("maps/idMap.json"));
$test = unserialize(file_get_contents("maps/fullOrders.bin"));
$list = new OrderList();
$list->orders=$test;

use Phpml\Association\Apriori;

$labels = [];
$classifier = new Apriori($support = 0.005, $confidence =0.001);
$classifier->train($read, $labels);

//$filepath = 'model';
//$modelManager = new ModelManager();
//$modelManager->saveToFile($classifier, $filepath);
//$restoredClassifier = $modelManager->restoreFromFile($filepath);
$value = $classifier->predict(['5246620663853'])[0][0];
echo $value;
print_r($list->GetRecordsThatContains("id", $value));