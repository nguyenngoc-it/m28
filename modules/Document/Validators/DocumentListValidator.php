<?php
namespace Modules\Document\Validators;

use App\Base\Validator;

class DocumentListValidator extends Validator
{
    public static $keyRequests = [
        'sort',
        'sortBy',
        'id',
        'type',
        'code',
        'status',
        'warehouse_id',
        'creator_id',
        'verifier_id',
        'created_at',
        'verified_at',
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
