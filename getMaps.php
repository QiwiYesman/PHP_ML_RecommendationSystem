<?php
require_once ('connect.php');
require_once ('OrderList.php');
$conn = connectToDB();
$table_name = 'orders';

$sql = "select * from $table_name";


if($result = $conn->query($sql))
{
    $orderList = new OrderList();
    while($row = $result->fetch_assoc())
    {
        $order = new OrderRecord();
        $data = unserialize($row["line_items"]);
        for($i=0; $i< count($data); $i++) {
            $rec = new ProductRecord();
            $rec->setFromArrayExt($data[$i]);
            $order->Add($rec);
        }
        $orderList->Add($order);
    }
    file_put_contents("maps/fullOrders.bin", serialize($orderList->orders));
    file_put_contents("maps/idMap.json", json_encode($orderList->GetMapList("id")));
    file_put_contents("maps/quantityMap.json", json_encode($orderList->GetMapList("quantity")));
    file_put_contents("maps/taxMap.json", json_encode($orderList->GetMapList("taxPrice")));
    file_put_contents("maps/vendorMap.json", json_encode($orderList->GetMapList("vendor")));
    file_put_contents("maps/discountMap.json", json_encode($orderList->GetMapList("discount")));
    file_put_contents("maps/priceMap.json", json_encode($orderList->GetMapList("price")));
}
else
{
    echo "Error: " . $conn->error;
}

$conn->close();