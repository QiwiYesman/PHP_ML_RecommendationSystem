<?php

require_once 'BaseConnection.php';
class RecommendSystem extends BaseConnection
{
    #pass here table name with costs;
    public string $cost_tableName="costs";
    public function recommendByTop(int $topAmount, int $perValueLimit=10, float $minCost=0.6): array
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

    public function recommendByValue($value, int $perValueLimit=10, float $minCost=0.6): array
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
                if(!in_array($product,$recommended)) {
                    $recommended[] = $product;
                    }
            }
        }
        return $recommended;
    }
}
$system = new RecommendSystem();
print_r($system->recommendByTop(20, 100, minCost: 0.5));