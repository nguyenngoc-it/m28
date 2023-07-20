<?php

namespace Modules\Category\Validators;

use App\Base\Validator;

class ListCategoryValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'sort',
        'sortBy',
        'parent_id',
        'tenant_id',
        'code',
        'name',
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
