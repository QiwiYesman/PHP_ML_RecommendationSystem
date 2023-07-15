<?php

require_once 'BaseConnection.php';
class RecommendSystem extends BaseConnection
{
    function recommendByTop(int $topAmount, int $perValueLimit=10, float $minCost=0.6): array
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

    function recommendByValue($value, int $perValueLimit=10, float $minCost=0.6): array
    {
        $recommended = [];
        $result = $this->conn->query(
            "select * from `costs` where 
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
print_r($system->recommendByTop(5, 100, minCost: 0.5));