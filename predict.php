<?php

use Phpml\ModelManager;

require_once ("./phpml/vendor/autoload.php");
$filepath="model";
$modelManager = new ModelManager();
$restoredClassifier = $modelManager->restoreFromFile($filepath);
print_r($restoredClassifier->predict(["5246620663853"]));