<?php

class ProductRecord
{
    public string
        $quantity,
        $id,
        $price,
        $discount,
        $vendor,
        $taxPrice;
    public function setFromArrayExt($array) : void
    {
        $this->quantity= $array['quantity'];
        $this->id=$array["product_id"];
        $this->price=$array["price"];
        $this->discount=$array["total_discount"];
        $this->vendor=$array["vendor"];
        if(!$array["taxable"])
        {
            $this->taxPrice="0";
            return;
        }
        $this->taxPrice=$array["tax_lines"][0]["price"];
    }

    public function toString() :string
    {
      return join("__", $this->toArray());
    }

    public function  toArray() : array
    {
        return [
            "quantity"=>$this->quantity,
            "id"=>$this->id,
            "price"=>$this->price,
            "discount"=>$this->discount,
            "vendor"=>$this->vendor,
            "taxPrice"=>$this->taxPrice,
        ];
    }

    public function commonFeatures(ProductRecord $record) : array
    {
        return array_intersect_assoc($this->toArray(),$record->toArray());
    }
}