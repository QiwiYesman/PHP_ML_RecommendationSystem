<?php

require_once 'connect.php';
class BaseConnection
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

}