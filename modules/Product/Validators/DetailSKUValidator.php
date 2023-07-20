<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Auth\Services\Permission;
use Modules\Product\Models\Sku;

class DetailSKUValidator extends Validator
{
    /** @var Sku $sku */
    protected $sku;

    /**
     * CreateSKUValidator constructor.
     * @param Sku $sku
     */
    public function __construct(Sku $sku)
    {
        parent::__construct([]);
        $this->sku = $sku;
    }

    protected function customValidate()
    {
        if (!array_intersect($this->sku->product->merchants->pluck('id')->all(), $this->user->merchants->pluck('id')->all())
            && !$this->user->can(Permission::PRODUCT_MANAGE_ALL)) {
            $this->errors()->add('code', 'not_to_access_product');
            return;
        }

        if (
            !$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT) &&
            !in_array($this->sku->product->supplier_id, $this->user->suppliers->pluck('id')->toArray())
        ) {
            $this->errors()->add('supplier_id', self::ERROR_INVALID);
            return;
        }
    }
}
