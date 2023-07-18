<?php

use lixam\PhpFPGrowth\FPGrowth;

require_once 'load_fp.php';
require_once 'BaseConnection.php';


class TopGathering extends BaseConnection
{
    #pass here table name with costs;
    public string $cost_tableName ='costs';
    public function getRelativeProducts(int $product_id) : array
    {
        $list = [];
        $sql = "select op2.orderID, op2.productID from $this->order_product_tableName op2 where
                op2.orderID in (select op.orderID from $this->order_product_tableName op 
                where op.productId =$product_id)";
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
    function array_in_array(array $toCheck, array $main) : bool
    {
        foreach ($main as $set)
        {
            if(count($toCheck) != count($set)) continue;
            $equal = true;
            for($i=0; $i<count($toCheck); $i++)
            {
                if($toCheck[$i] != $set[$i])
                {
                    $equal = false;
                    break;
                }
            }
            if($equal) return true;
        }
        return false;
    }
    function rulesToSets(array $rules) : array
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

    function trainTopSets(int $topAmount, $support =3, $confidence =0.3): FPGrowth
    {
        $samples = $this->getSamplesByTop($topAmount);
        $model = $this->train($samples, $support, $confidence);
        $sets = $this->rulesToSets($model->getRules());
        $this->saveCosts($sets);
        return $model;
    }

    function getSamplesByTop(int $topAmount) :array
    {
        $samples = [];
        $top = $this->getTop($topAmount);
        foreach ($top as $product_id=>$dummy)
        {
            $orders_products = $this->getRelativeProducts($product_id);
            foreach ($orders_products as $relations)
            {
                if($this->array_in_array($relations, $samples)) continue;
                $samples[] = $relations;
            }
        }
        return $samples;
    }

    public function train(array $samples, $support=3, $confidence=0.3): FPGrowth
    {
        $fp = new FPGrowth($support, $confidence);
        $fp->run($samples);
        return $fp;
    }

    public function saveModel(FPGrowth $model, string $filePath) : void
    {
        file_put_contents($filePath, serialize($model));
    }

    public function loadModel(string $filePath): FPGrowth
    {
        return unserialize(file_get_contents($filePath));
    }

    public function saveCosts(array $set_sample_costs, bool $toTruncate=true): void
    {
        if($toTruncate)
        {
            $this->conn->query(
                "truncate table `costs`");
        }
        $sql = "insert into `$this->cost_tableName` values";
        foreach ($set_sample_costs as $set_sample_cost)
        {
            $str_set = serialize($set_sample_cost['set']);
            $predicted_value = $set_sample_cost['main'];
            $cost= $set_sample_cost['cost'];
            $sql.= "('".$str_set."',".$predicted_value.",".$cost."),";
        }
        $this->conn->query(rtrim($sql,","));
    }
}

/*
 #use this to train model and save results into table
$gather = new TopGathering();
$model = $gather->trainTopSets(100, 3, 0.1);
$gather->saveModel($model, "./models/fp_model");
*/