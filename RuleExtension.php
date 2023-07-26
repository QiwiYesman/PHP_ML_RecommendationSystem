<?php

require_once __DIR__.'/BaseConnection.php';
require_once __DIR__.'/TrainMethods.php';

/**
 * Class takes the already trained data (any cost table) and adding non-existing rules
 * These rules are created by the next rule: if AB => C = 0.5 and C=>K = 0.5 then ABC => K = 0.25
 * It is not correct for associating mining rules, but it has some logic for recommendations.
 * Of course, if there is no rule (or too small confidence) for ABC=>K, it can have some reason for it.
 * But it is rather an implementation of inaccurate assumptions than exact predictions
 */
class RuleExtension extends BaseConnection
{
    public array $count = [];
    public array $simple_rules = [];
    public array $new_rules = [];
    public array $old_rules = [];
    public array $trainTablesByMethods = ["costs", "costs2", "costs3"];
    public array $saveTablesByMethods = ["costsExt", "costs2ext", "costs3ext"];
    public string $save_tableName="costsExt";
    public string $train_tableName="costs";


    /**
     * Switches names for tables for saving and training according to training method was used before
     * @param int $methodEnum train method enum, see TrainMethods.php
     * @return void
     */
    public function switchTableMyMethod(int $methodEnum): void
    {
        $this->train_tableName = $this->trainTablesByMethods[$methodEnum];
        $this->save_tableName = $this->saveTablesByMethods[$methodEnum];
    }

    /**
     * Gets rules from database (train table).<br>
     * It must be filled table, trained previously with one of methods
     * @param int $limit max number to read
     * @param float $minCost min cost to read from table. Remember that final costs will be less
     * @return void <br> It saves data inside
     */
    public function readRules(int $limit = 1000, float $minCost=0.4): void
    {
        $this->old_rules = [];
        $result = $this->conn->query(
            "select * from `$this->train_tableName` where `cost`>=$minCost limit $limit;");
        while ($row = $result->fetch_assoc())
        {
            $set = unserialize($row["sets"]);
            $this->old_rules[]= [
                "set"=>$set,
                "main"=>$row["main"],
                "cost"=> $row["cost"]
            ];

        }
    }

    /**
     * Gets unique values from 'main' field
     * @return array unique 'main' values
     */
    public function getUniqueMainValues() : array
    {
        $mainValues = [];
        foreach ($this->old_rules as $rule)
        {
            if(in_array($rule["main"],$mainValues)) continue;
            $mainValues[] = $rule["main"];
        }
        return $mainValues;
    }

    /**
     * Calculates the number of each productID in the read from table rules
     * @param int|string $productID
     * @return void <br> it saving count in $count([productID=>count]) array
     */
    public function calculateSupport(int|string $productID) : void
    {
        if(!in_array($productID, $this->count))
        {
            $this->count[$productID] = 0;
        }
        foreach ($this->old_rules as $rule)
        {
            if(!in_array($productID, $rule['set'])) continue;
            $this->count[$productID]++;
        }
        if($this->count[$productID] != 0) return;
        unset($this->count[$productID]);
    }

    /**
     * Creates rules for each unique product
     * @return void
     */
    public function calculateSimpleRules() :void
    {
        $unique = $this->getUniqueMainValues();
        foreach ($unique as $item) {
            $this->calculateSupport($item);
        }
        foreach ($this->count as $product=>$support)
        {
            $this->calculateSimpleRulesForProduct($product);
        }
    }

    /**
     * Creates rules for the specified product <br>
     * Every rule has the next structure:<br>
     * $productID=>[[neighbour => confidence], [neighbour2=>confidence2] ... ]
     * @param int|string $productID target productID
     * @return void
     *  <br> Generated rules are storing info about antecedent value, consequent one and new `confidence`
     */
    public function calculateSimpleRulesForProduct(int|string $productID): void
    {
        $count = [];
        $this->simple_rules[$productID] = [];
        foreach ($this->old_rules as $rule)
        {
            if(!in_array($productID, $rule['set'])) continue;
            if(!in_array($rule['main'], array_keys($count)))
            {
                $count[$rule['main']] = 0;
            }
            $count[$rule['main']]++;
        }
        foreach ($count as $product=>$amount)
        {
            $this->simple_rules[$productID][$product]= $amount/$this->count[$productID];
        }

    }

    /**
     * The whole cycle of training, the main function to use
     * @param int $limit
     * @param $minConfidence
     * @return void
     */
    public function train(int $limit =1000, float $minConfidence=0.2): void
    {
        $this->readRules($limit, $minConfidence);
        $this->calculateSimpleRules();
        $this->generateNewRules();
    }

    /**
     * Generates new rules by the above written scheme
     * @return void
     */
    public function generateNewRules(): void
    {
        foreach ($this->simple_rules as $product=>$neighbours)
        {
            foreach ($this->old_rules as $oldRule)
            {
                if($oldRule['main']!=$product) continue;

                foreach ($neighbours as $neighbour=>$confidence)
                {
                    if(in_array($neighbour, $oldRule['set'])) continue;
                    $prob = $oldRule['cost']*$confidence;
                    $merged = array_merge($oldRule['set'], [$product]);
                    if($this->isExisting(
                        ['antecedent'=>$merged,
                        'consequent'=>$neighbour])) continue;
                    $this->new_rules[] = [
                        'set'=> $merged,
                        'main'=>$neighbour,
                        'cost'=>$prob
                    ];
                }

            }
        }
    }

    /**
     * Checks if new rule already exists in the dataset. Maybe, this function is obsolete
     * @param array $new_rule rule to check
     * @return bool true, if rule already exists
     */
    private function isExisting(array $new_rule): bool
    {
        foreach ($this->old_rules as $oldRule)
        {
            if($new_rule['consequent'] == $oldRule['main']
                && count(array_intersect($new_rule['antecedent'], $oldRule['set']))
                ==count($new_rule['antecedent']))
            {
                return true;
            }
        }
        return false;
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
                "truncate table `$this->save_tableName`");
        }
        $sql = "insert into `$this->save_tableName` values";
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
