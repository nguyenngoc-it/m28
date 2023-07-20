<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;

class ImportedSkuInventoryValidator extends Validator
{
    /** @var Sku|null $sku */
    protected $sku;
    /** @var int|null */
    protected $quantityChecked;

    /** @var Merchant | null */
    protected $merchant;

    /**
     * ImportedSkuInventoryValidator constructor.
     * @param User $user
     * @param array $row
     * @param null $merchant
     */
    public function __construct(User $user, array $row, $merchant = null)
    {
        parent::__construct($row, $user);
        $this->user = $user;
        $this->merchant = $merchant;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'sku_code' => 'required',
        ];
    }

    /**
     * @return Sku|null
     */
    public function getSku(): ?Sku
    {
        return $this->sku;
    }

    /**
     * @return int|null
     */
    public function getQuanityChecked(): ?int
    {
        return $this->quantityChecked;
    }

    protected function customValidate()
    {
        $skuCode               = $this->input('sku_code');
        $this->quantityChecked = $this->input('quantity_checked');

        $merchantIds = $this->user->merchants()->where([
            'status' => true,
            'tenant_id' => $this->user->tenant_id
        ])->pluck('merchants.id')->toArray();

        $skuQuery = Sku::query()->select(['skus.*'])->where('skus.tenant_id', $this->user->tenant_id)
            ->join('products', 'skus.product_id', '=', 'products.id')
            ->join('product_merchants', 'products.id', '=', 'product_merchants.product_id')
            ->where('skus.code', $skuCode)
            ->whereIn('product_merchants.merchant_id', $merchantIds);

        if($this->merchant instanceof Merchant) {
            $skuQuery->where('product_merchants.merchant_id', $this->merchant->id);
        }
        $skuQuery->groupBy('skus.id');
        $skus = $skuQuery->get();
        if($skus->count() > 1) {
            $this->errors()->add('has_many_in_merchant', $skuCode);
            return false;
        }
        $this->sku = $skuQuery->first();

        if(!$this->sku instanceof Sku) {
            $this->errors()->add('sku_code', static::ERROR_EXISTS);
            return;
        }
    }
}
