<?php

require_once 'BaseConnection.php';
require_once 'TrainMethods.php';
class RecommendSystem extends BaseConnection
{
    private array $tablesByMethods = ["costs", "costs2", "costs3"];
    public string $cost_tableName="costs";

    /**
     * Get recommendations from a table
     * @param int $topAmount    max number of rows to select from top by count
     * @param int $perValueLimit    max number of relative sets per every product from top.
     * It is not implied that you will get the max number if you have in table more than max rows;
     * If similar values will appear, they are counted. Then only one value from duplicates will remain
     * @param float $minCost    min cost (confidence). If cost of record is less, it is not considered
     * @return array    set of recommendations. It has only unique values. It CAN contain productIDs from top
     */
    public function recommendByTop(int $topAmount, int $perValueLimit=20, float $minCost=0.6): array
    {
        $recommendations =[];
        $top = $this->getTop($topAmount);
        foreach ($top as $topValue=>$count)
        {
            $recommended = $this->recommendByValue($topValue, $perValueLimit, $minCost);
            foreach ($recommended as $product)
            {
                if(!in_array($product,$recommendations))
                {
                    $recommendations[] = $product;
                }
            }
        }
        return $recommendations;
    }
    /**
     * Get recommendations from a table
     * @param int $topAmount    max number of rows to select from top (ordered by cost value)
     * @param int $perValueLimit    max number of relative sets per every product from top.
     * It is not implied that you will get the max number if you have in table more than max rows;
     * If similar values will appear, they are counted. Then only one value from duplicates will remain
     * @param float $minCost    min cost (confidence). If cost of record is less, it is not considered
     * @return array    set of recommendations. It has only unique values. It CANNOT contain productIDs from top
     */
    public function recommendByTopWithoutTop(int $topAmount, int $perValueLimit=20, float $minCost=0.6):array
    {
        $recommendations =[];
        $top = $this->getTop($topAmount);
        foreach ($top as $topValue=>$count)
        {
            $recommended = $this->recommendByValue($topValue, $perValueLimit, $minCost);
            foreach ($recommended as $product)
            {
                if(!in_array($product,$recommendations))
                {
                    $toAdd = true;
                    foreach ($top as $topValue=>$_)
                    {
                        if($topValue != $product) continue;
                        $toAdd = false;
                        break;
                    }
                    if(!$toAdd) continue;
                    $recommendations[] = $product;
                }
            }
        }
        return $recommendations;

    }

    /**
     * Get relative to product sets
     * @param int|string $value    productID
     * @param int $perValueLimit    max amount of antecedent sets
     * @param float $minCost    min cost (confidence) needed to pull out a record from table
     * @return array    set of antecedent products relative to product with productID.
     * Size of set can be various and weakly depends on perValueLimit
     * because the unique products return and different sets can have similar products
     */
    public function recommendByValue(int|string $value, int $perValueLimit=10, float $minCost=0.6): array
    {
        $recommended = [];
        $result = $this->conn->query(
            "select * from `$this->cost_tableName` where 
                          `main`=$value and `cost`>=$minCost 
                      order by `cost` desc limit $perValueLimit");
        while ($row = $result->fetch_assoc())
        {
            $products = unserialize($row["sets"]);
            foreach ($products as $product)
            {
                if(!in_array($product,$recommended))
                {
                    $recommended[] = $product;
                }
            }
        }
        return $recommended;
    }

    /**
     * Get recommendations.
     * Recommendations are selected from all tables and then merged.
     * Current table remains as for last training method.
     * @param int $topAmount    max number of rows to select from top (ordered by cost value)
     * @param int $perValueLimit    max number of relative sets per every product from top.
     * It is not implied that you will get the max number if you have in table more than max rows;
     * If similar values will appear, they are counted. Then only one value from duplicates will remain
     * @param float $minCost    min cost (confidence). If cost of record is less, it is not considered
     * @return array    set of recommendations. It has only unique values. It CAN contain productIDs from top.
     */
    public function recommendByTopFromAllTables(int $topAmount, int $perValueLimit=20, float $minCost=0.6) :array
    {
        $this->switchTableMyMethod(TrainMethods::FPGrowth);
        $fp =$this->recommendByTop($topAmount, $perValueLimit, $minCost);
        $this->switchTableMyMethod(TrainMethods::Apriori);
        $apriori =$this->recommendByTop($topAmount, $perValueLimit, $minCost);
        $this->switchTableMyMethod(TrainMethods::Eclat);
        $eclat =$this->recommendByTop($topAmount, $perValueLimit, $minCost);
        return  array_unique(array_merge($eclat, $apriori, $fp));
    }

    /**
     * Get recommendations. Recommendations are selected from all tables and then merged.
     * Current table remains as for last training method.
     * @param int $topAmount    max number of rows to select from top (ordered by cost value)
     * @param int $perValueLimit    max number of relative sets per every product from top.
     * It is not implied that you will get the max number if you have in table more than max rows;
     * If similar values will appear, they are counted. Then only one value from duplicates will remain
     * @param float $minCost    min cost (confidence). If cost of record is less, it is not considered
     * @return array    set of recommendations.
     * It has only unique values. It CANNOT contain productIDs from top.
     **/
    public function recommendByTopFromAllTablesWithoutTop(int $topAmount, int $perValueLimit=20, float $minCost=0.6) :array
    {
        $this->switchTableMyMethod(TrainMethods::FPGrowth);
        $fp =$this->recommendByTopWithoutTop($topAmount, $perValueLimit, $minCost);
        $this->switchTableMyMethod(TrainMethods::Apriori);
        $apriori =$this->recommendByTopWithoutTop($topAmount, $perValueLimit, $minCost);
        $this->switchTableMyMethod(TrainMethods::Eclat);
        $eclat =$this->recommendByTopWithoutTop($topAmount, $perValueLimit, $minCost);
        return  array_unique(array_merge($eclat, $apriori, $fp));
    }
    /**
     * @param array $array  the set of pulled recommendations
     * @param int $limit    max size of returned array
     * @return array    sliced up to $limit recommendations.
     * Previously array is shuffled to make it always to be various
     */
    public function limit(array $array, int $limit=10) : array
    {
        if($limit <= 0) $limit =1;
        if(count($array)<=1 or count($array)<=$limit) return $array;
        shuffle($array);
        return array_slice($array, 0, $limit);
    }

    /**
     * Switches the table name to get data from one;
     * It depends on a chosen train method (look at TrainMethods)
     * @param int $methodEnum look at class TrainMethods that has constants
     * @return void
     **/
    public function switchTableMyMethod(int $methodEnum): void
    {
        $this->cost_tableName = $this->tablesByMethods[$methodEnum];
    }
}


