<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Tenant\Models\Tenant;

class MerchantUpdateProductStatusValidator extends Validator
{
    /**
     * @var Tenant|null
     */
    protected $tenant = null;

    /**
     * @var Merchant|null
     */
    protected $merchant = null;

    /** @var Product[] $products */
    protected $products;

    /**
     * MerchantUpdateProductStatusValidator constructor.
     * @param Merchant $merchant
     * @param array $input
     */
    public function __construct(Merchant $merchant, array $input = [])
    {
        parent::__construct($input);
        $this->tenant = $merchant->tenant;
    }

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'ids' => 'required|array',
            'status' => 'required|in:'. Product::STATUS_WAITING_FOR_QUOTE.','.Product::STATUS_WAITING_CONFIRM,
        ];
    }

    /**
     * @return Product[]|Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    protected function customValidate()
    {
        $this->products = $this->tenant->products()->whereIn('id', $this->input['ids'])->get();

        if (!$this->products->count()) {
            $this->errors()->add('ids', self::ERROR_INVALID);
            return;
        }

        $status = trim($this->input('status'));
        foreach ($this->products as $product) {
            if(!$product->canChangeStatus($status)) {
                $this->errors()->add('product_not_can_change_status', $product->code);
                return;
            }
        }
    }
}
