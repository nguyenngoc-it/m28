<?php

namespace Modules\ImportHistory\Validators;

use App\Base\Validator;

class ListImportHistoryItemValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'tenant_id', 'merchant_id', 'sku_id', 'warehouse_id', 'warehouse_area_id', 'stock', 'freight_bill',
        'package_code', 'note', 'import_history_id', 'sku_name', 'sku_code'
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
