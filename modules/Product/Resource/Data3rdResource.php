<?php

namespace Modules\Product\Resource;


class Data3rdResource 
{

    /**
     * Title Sku Combo
     *
     * @var [string]
     */
    public $name;
    /**
     * Code Sku Combo
     *
     * @var string
     */
    public $code;
    /**
     * Danh mục
     *
     * @var string
     */
    public $category_id;

    /**
     * Danh mục
     *
     * @var string
     */
    public $merchant_id;
    /**
     * Source sku combo
     *
     * @var string
     */
    public $source;
    /**
     * Market place
     *
     * @var string
     */
    public $marketplace_code;
    /**
     * Giá trị Sku Combo
     *
     * @var float
     */
    public $price;
    /**
     * Danh sách Sku Item Của Sku Combo
     * [
        'id'              => Sku id,
        'price'           => Base Price Of Sku Item,
        'quantity'        => Quantity Of Sku Item,
        ]
     *
     * @var array
     */
    public $items;
}