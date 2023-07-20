<?php

namespace Modules\Product\Validators;

use App\Base\Validator;

class ListProductValidator extends Validator
{
    protected $project = '';

    /**
     * Các key filter
     */
    public static $keyRequests = [
        'sort',
        'sortBy',
        'tenant_id',
        'code',
        'name',
        'status',
        'category_id',
        'unit_id',
        'created_at',
        'page',
        'per_page',
        'ubox_product_code',
        'merchant_id',
    ];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'sort' => 'in:desc,asc',
            'sortBy' => 'in:id,created_at,code,name',
            'page' => 'numeric',
            'per_page' => 'numeric',
        ];
    }
}
