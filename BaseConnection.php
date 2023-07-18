<?php

require_once 'connect.php';
class BaseConnection
{
    public mysqli $conn;

    #pass here table names with product_count relation and order_product relation
    public string $product_count_tableName= 'product_count';
    public string $order_product_tableName= 'order_product';

    #pass here table name with order history to get main values;
    public string $orderMain_tableName = "orders";

    public function __construct()
    {
        $this->conn = connectToDB();
    }
    public function __destruct()
    {
        $this->conn->close();
    }

    public function getTop(int $topAmount) : array
    {
        $list = [];
        $sql = "select * from $this->product_count_tableName order by `count` desc limit $topAmount;";
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

    public function cropMainTable() : void
    {
        if($result = $this->conn->query("select id, line_items from `orders`"))
        {
            $product_count = [];
            $order_product = [];
            while($row = $result->fetch_assoc())
            {
                $data = unserialize($row["line_items"]);
                $order_id = $row["id"];
                $temp_products = [];
                for($i=0; $i< count($data); $i++)
                {
                    if(!in_array($data[$i]["product_id"], $temp_products))
                    {
                        $temp_products[] = $data[$i]["product_id"];
                    }
                }
                foreach ($temp_products as $product_id)
                {
                    if(!key_exists($product_id, $product_count))
                    {
                        $product_count[$product_id] = 0;
                    }
                    $product_count[$product_id]++;
                    $order_product[] = [$order_id, $product_id];
                }
            }

            foreach ($product_count as $product_id=>$count)
            {
                $this->conn->query(
                    "insert into `$this->product_count_tableName`(`product_id`, `count`) values ($product_id, $count)");
            }
            foreach ($order_product as $item)
            {
                $this->conn->query(
                    "insert into `$this->order_product_tableName`(`orderID`, `productID`) values ($item[0], $item[1])");
            }
        }
        else
        {
            echo "Error: " . $this->conn->error;
        }
    }
}