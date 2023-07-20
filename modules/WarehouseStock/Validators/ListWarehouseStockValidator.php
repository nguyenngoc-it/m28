<?php

namespace Modules\WarehouseStock\Validators;

use App\Base\Validator;

class ListWarehouseStockValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'sort',
        'sortBy',
        'warehouse_id',
        'out_of_stock',
        'sku_status',
        'product_keyword',
        'sku_keyword',
        'product_id',
        'product_code',
        'product_name',
        'sku_id',
        'sku_code',
        'sku_name',
        'saleable_quantity',
        'quantity',
        'real_quantity',
        'created_at',
        'page',
        'per_page',
    ];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'sort' => 'in:desc,asc',
            'sortBy' => 'in:id,updated_at,created_at',
            'page' => 'numeric',
            'per_page' => 'numeric',
        ];
    }
}
