<?php
#Use only once, when tables are empty

require_once ('connect.php');
$conn = connectToDB();
$product_count = [];
$order_product = [];
if($result = $conn->query("select id, line_items from `orders`"))
{
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
        $conn->query("insert into `product_count`(`product_id`, `count`) values ($product_id, $count)");
    }
    foreach ($order_product as $item)
    {
        $conn->query("insert into `order_product`(`orderID`, `productID`) values ($item[0], $item[1])");
    }
}
else
{
    echo "Error: " . $conn->error;
}

$conn->close();