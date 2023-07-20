<?php

namespace Modules\Product\Validators;

use App\Base\Validator;

class ListSkuValidator extends Validator
{
    protected $project = '';

    /**
     * Các key filter
     */
    public static $keyRequests = [
        'keyword',
        'sort',
        'sortBy',
        'tenant_id',
        'creator_id',
        'product_id',
        'supplier_id',
        'product_code',
        'product_name',
        'id',
        'code',
        'sku_codes',
        'name',
        'inventory',
        'status',
        'category_id',
        'unit_id',
        'created_at',
        'page',
        'per_page',
        'nearly_sold_out',
        'merchant_id'
    ];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'sort' => 'in:desc,asc',
            'sortBy' => 'in:id,updated_at,created_at,code,name',
            'page' => 'numeric',
            'per_page' => 'numeric',
        ];
    }
}
