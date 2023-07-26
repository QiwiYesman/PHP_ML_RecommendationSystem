<?php

use lixam\PhpFPGrowth\FPGrowth;

require_once __DIR__.'/load_fp.php';
require_once __DIR__.'/BaseConnection.php';

/**
 * Base class with methods to prepare for training and common train methods
 */
abstract class PrepareTraining extends BaseConnection
{
    #pass here table name with costs;
    public string $cost_tableName ='costs';
    public mixed $model;
    public abstract function model():mixed;
    /**
     * Get orders contain the productID
     * @param int $product_id productID to build sql query
     * @return string sql query to select orders with the passed productID
     */
    public function sqlToGetRelativeProducts(int $product_id): string
    {
        return "select op2.orderID, op2.productID from $this->order_product_tableName op2 where
                op2.orderID in (select op.orderID from $this->order_product_tableName op 
                where op.productId =$product_id)";
    }

    /**
     * Get list of product sets from the same order that contains productID
     * @param int $product_id productID
     * @return array list of product sets
     */
    public function getRelativeProducts(int $product_id) : array
    {
        $sql = $this->sqlToGetRelativeProducts($product_id);
        $list = [];
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

    /**
     * Checks if array $toCheck is a part of $main array. Maybe, array_difference is better
     * @param array $toCheck array to check if it is contained
     * @param array $main array to check if it contains
     * @return bool true if $toCheck is in array
     */
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

    /**
     * Translates got rules from an algorithm to the common struct array
     * @param array $rules rules from train method
     * @return array sets with the struct <br>
     * [set=>[A, B, C... (productIDs)],<br>
     * main=>K (productID), <br>
     * cost=>float from 0 to 1]
     */
    public abstract function rulesToSets(array $rules) : array;

    /**
     * Common method to train sets
     * @param int $topAmount
     * @param int $support minimal support (absolute value)
     * @param float $confidence minimal confidence
     * @return array sets from ruleToSets method
     */
    public function trainTopSets(int $topAmount, int $support =3, float $confidence =0.3): array
    {
        $samples = $this->getSamplesByTop($topAmount);
        $this->train($samples, $support, $confidence);
        $sets = $this->rulesToSets($this->model->getRules());
        $this->saveCosts($sets);
        return $sets;
    }

    /**
     * Get list of product sets. <br>
     * For each productID from top getRelativeProducts is called and merged with main list
     * @param int $topAmount max number of top elements
     * @return array samples to train
     */
    function getSamplesByTop(int $topAmount) :array
    {
        $samples = [];
        $top = $this->getTop($topAmount);
        foreach ($top as $product_id=>$_)
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

    /**
     * Run a training algorithm
     * @param array $samples samples to train
     * @param int $support min support (absolute value)
     * @param float $confidence min confidence
     * @return void
     */
    public abstract function train(array $samples, int $support=3, float $confidence=0.3) : void;

    /**
     * Saving the model from Training`ClassName` to a file.
     * @param string $filePath string path to save (with file name)
     * @return void
     */
    public function saveModel(string $filePath) : void
    {
        file_put_contents($filePath, serialize($this->model));
    }

    /**
     * Restoring the model from a file
     * @param string $filePath string path to restore (with file name)
     * @return void
     */
    public function loadModel(string $filePath) : void
    {
        $this->model = unserialize(file_get_contents($filePath));
    }


    /**
     * Saving trained costs to a table (each training model has own table)
     * @param array $set_sample_costs sets from method trainTopSets
     * @param bool $toTruncate if true, truncates the table and then inserts values.
     * <br> Else inserts to the end of the table
     * @return void
     */
    public function saveCosts(array $set_sample_costs, bool $toTruncate=true): void
    {
        if($toTruncate)
        {
            $this->conn->query(
                "truncate table `$this->cost_tableName`");
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
