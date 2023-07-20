<?php

namespace Modules\Order\Validators;

use App\Base\Validator;

class ListOrderValidator extends Validator
{
    protected $project = '';

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
