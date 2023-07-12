<?php
require_once 'ProductRecord.php';
class OrderRecord
{
    public array $records;

    public function __construct()
    {
        $this->records = [];
    }

    public function Add(ProductRecord $record): void
    {
        $this->records[] = $record->toArray();
    }

    public function Contains($fieldName, $fieldValue): bool
    {
        foreach ($this->records as $record)
        {
            if($record[$fieldName] == $fieldValue)
            {
                return true;
            }
        }
        return false;
    }
    public function GetMap($fieldName): array
    {
        $arr = [];
        foreach ($this->records as $record)
        {
            $arr[] = $record[$fieldName];
        }
        return $arr;
    }
}