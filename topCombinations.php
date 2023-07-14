<?php

require_once './phpml/vendor/autoload.php';
require_once ('connect.php');
use Phpml\Classification\SVC;
use Phpml\ModelManager;
use Phpml\SupportVectorMachine\Kernel;

class TopGathering
{
    public mysqli $conn;

    public function __construct()
    {
        $this->conn = connectToDB();
    }
    public function __destruct()
    {
        $this->conn->close();
    }

    function getTop(int $topAmount) : array
    {
        $list = [];
        $sql = "select * from product_count order by `count` desc limit $topAmount;";
        if($result = $this->conn->query($sql))
        {
            while($row = $result->fetch_assoc())
            {
                $list[$row["product_id"]] = $row["count"];
            }
        }
        else
        {
            echo "Error: " . $this->conn->error;
        }
        return $list;
    }

    function getRelativeProductIds(int $product_id) : array
    {
        $list = [];
        $sql = "select op2.orderID, op2.productID from order_product op2 where
                op2.orderID in (select op.orderID from order_product op 
                where op.productId =$product_id) and op2.productID <> $product_id;";
        if($result = $this->conn->query($sql))
        {
            while($row = $result->fetch_assoc())
            {
                $order_id = $row["orderID"];
                if(!key_exists($order_id, $list))
                {
                    $list[$order_id] = [];
                }
                $list[$order_id][] = $row["productID"];
            }
        }
        else
        {
            echo "Error: " . $this->conn->error;
        }
        return $list;
    }

    function getSamplesAndLabelsByTop(int $topAmount) :array
    {
        $samples = [];
        $labels = [];
        $top = $this->getTop($topAmount);
        foreach ($top as $product_id=>$dummy)
        {
            $orders_products = $this->getRelativeProductIds($product_id);
            foreach ($orders_products as $neighbours)
            {
                $samples[] = $neighbours;
                $labels[] = $product_id;
            }
        }
        return [
            "samples"=>$samples,
            "labels"=>$labels
        ];
    }

    public function train(array $samples, array $labels): SVC
    {
        $classifier = new SVC(probabilityEstimates: true);
        $classifier->train($samples, $labels);
        return $classifier;
    }

    public function saveModel(SVC $classifier, string $filePath) : void
    {
        file_put_contents($filePath, serialize($classifier));
    }

    public function loadModel(string $filePath): SVC
    {
        return unserialize(file_get_contents($filePath));
    }

    public function predictProbabilities(SVC $classifier, array $samples) : array
    {
        $set_sample_costs = [];
        foreach ($samples as $set)
        {
            $costs = $classifier->predictProbability($set);
            foreach ($costs as $predicted_value=>$cost)
            {
                $set_sample_costs[] = [
                    "set"=>serialize($set),
                    "main"=>$predicted_value,
                    "cost"=>$cost];
            }
        }
        return $set_sample_costs;
    }

    public function predictSaveProbabilities(SVC $classifier, array $samples, bool $toTruncate=true): void
    {
        if($toTruncate)
        {
            $this->conn->query(
                "truncate table `probabilities`");
        }
        $sql = "insert into `probabilities` values";

        foreach ($samples as $set)
        {
            $costs = $classifier->predictProbability($set);
            foreach ($costs as $predicted_value=>$cost)
            {
                $str_set = serialize($set);
                $sql.= "('".$str_set."',".$predicted_value.",".$cost."),";
            }
        }
        echo 'inserting';
        $this->conn->query(rtrim($sql,","));

    }
    public function saveProbabilities(array $set_sample_costs, bool $toTruncate=true): void
    {
        if($toTruncate)
        {
            $this->conn->query(
                "truncate table `probabilities`");
        }
        $sql = "insert into `probabilities` values";
        foreach ($set_sample_costs as $set_sample_cost)
        {
            $str_set = serialize($set_sample_cost['set']);
            $predicted_value = $set_sample_cost['main'];
            $cost=  $set_sample_cost['cost'];
            $sql.= "('".$str_set."',".$predicted_value.",".$cost."),";
        }
        echo 'inserting';
        $this->conn->query(rtrim($sql,","));
    }
}

$t_gather = new TopGathering();
$arr = $t_gather->getSamplesAndLabelsByTop(100);
//$model = $t_gather->train($arr["samples"], $arr["labels"]);
//$t_gather->saveModel($model, "./models/try_model");
$model = $t_gather->loadModel("./models/try_model");
foreach ($arr['samples'] as $s)
{
    if($model->predict($s) != '5246620663853')
    {
        echo "true";
    }

}

echo 'here';
//$t_gather->predictSaveProbabilities($model, $arr["samples"], true);

