<?php

namespace Modules\DeliveryNote\Validators;

use App\Base\Validator;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;

class ListDeliveryNoteValidator extends Validator
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $skus = [];

    /**
     * @var string[]
     */
    public static $acceptKeys = [
        'warehouse_id',
        'id',
        'creator_id',
        'created_at_from',
        'created_at_to',
        'page',
        'per_page',
        'sort',
        'sortBy',
    ];

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
