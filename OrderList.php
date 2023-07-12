<?php

require_once 'OrderRecord.php';
class OrderList
{
    public array $orders;

    public function __construct()
    {
        $this->orders = [];
    }

    public function Add(OrderRecord $record): void
    {
        $this->orders[] = $record;
    }

    public function GetMapList($fieldName): array
    {
        $list = [];
        foreach ($this->orders as $order)
        {
            $list[] = $order->GetMap($fieldName);
        }
        return $list;
    }

    public function GetRecordsThatContains($fieldName, $fieldValue) : array
    {
        $list = [];
        foreach ($this->orders as $order)
        {
            if($order->Contains($fieldName, $fieldValue))
            {
                $list[] = $order->records;
            }
        }
        return $list;
    }

}