<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;

class ListMerchantValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'sort',
        'sortBy',
        'status',
        'tenant_id',
        'location_id',
        'phone',
        'code',
        'name',
        'ref',
        'created_at',
        'page',
        'per_page',
        'paginate'
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
