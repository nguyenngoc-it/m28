<?php

namespace Modules\ImportHistory\Validators;

use App\Base\Validator;

class ListImportHistoryValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'sort',
        'sortBy',
        'tenant_id',
        'code',
        'sku_id',
        'warehouse_area_id',
        'warehouse_id',
        'creator_id',
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
            'sortBy' => 'in:id,updated_at,created_at',
            'page' => 'numeric',
            'per_page' => 'numeric',
        ];
    }
}
