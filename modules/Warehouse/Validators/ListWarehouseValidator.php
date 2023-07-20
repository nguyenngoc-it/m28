<?php

namespace Modules\Warehouse\Validators;

use App\Base\Validator;

class ListWarehouseValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'sort',
        'sortBy',
        'status',
        'tenant_id',
        'code',
        'name',
        'keyword',
        'country_id',
        'province_id',
        'district_id',
        'ward_id',
        'created_at',
        'page',
        'per_page'
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
