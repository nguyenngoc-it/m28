<?php

namespace Modules\Stock\Validators;

use App\Base\Validator;

class ListStockValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'sort',
        'sortBy',
        'groupBy',
        'id',
        'warehouse_area_id',
        'warehouse_id',
        'creator_id',
        'sku_id',
        'sku_code',
        'sku_name',
        'merchant_id',
        'out_of_stock',
        'created_at',
        'page',
        'per_page',
        'for_delivery_note'
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
            'out_of_stock' => 'bool',
        ];
    }
}
