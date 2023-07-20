<?php

namespace Modules\Location\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;

class ListLocationValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'sort',
        'sortBy',
        'tenant_id',
        'code',
        'label',
        'type',
        'parent_code',
        'detail',
        'active',
        'priority',
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
            'type' => 'required|in:'.implode(',', Location::$types),
            'sort' => 'in:desc,asc',
            'sortBy' => 'in:id,created_at,code,label',
            'page' => 'numeric',
            'per_page' => 'numeric',
        ];
    }

    protected function customValidate()
    {
        $type = $this->input['type'];
        if ($type != Location::TYPE_COUNTRY && empty($this->input('parent_code'))) {
            $this->errors()->add('parent_code', static::ERROR_REQUIRED);
            return;
        }
    }
}