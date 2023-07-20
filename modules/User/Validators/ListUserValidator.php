<?php

namespace Modules\User\Validators;

use App\Base\Validator;

class ListUserValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'sort',
        'sortBy',
        'tenant_id',
        'email',
        'username',
        'phone',
        'name',
        'merchant_id',
        'warehouse_id',
        'location_id',
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
            'sortBy' => 'in:id,created_at,username,name',
            'page' => 'numeric',
            'per_page' => 'numeric',
        ];
    }
}
