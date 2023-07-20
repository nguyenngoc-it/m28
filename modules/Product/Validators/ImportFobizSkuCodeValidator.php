<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class ImportFobizSkuCodeValidator extends Validator
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var Sku
     */
    protected $sku;


    /**
     * @var Store
     */
    protected $store;

    /**
     * @var array
     */
    protected $insertedSkuKeys = [];

    /**
     * ImportFobizSkuCodeValidator constructor.
     * @param User $user
     * @param Store $store
     * @param array $input
     * @param array $insertedSkuKeys
     */
    public function __construct(User $user, Store $store, array $input, $insertedSkuKeys = [])
    {
        $this->user  = $user;
        $this->store = $store;
        $this->tenant = $user->tenant;
        $this->insertedSkuKeys = $insertedSkuKeys;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'sku_code' => 'required',
            'fobiz_code' => 'required',
        ];
    }

    protected function customValidate()
    {
        if (!$this->sku = $this->tenant->skus()->firstWhere('code', $this->input['sku_code'])) {
            $this->errors()->add('sku_code', static::ERROR_NOT_EXIST);
            return;
        }

        if ($this->store->storeSkus()->firstWhere('code', $this->input['fobiz_code'])) {
            $this->errors()->add('fobiz_code', static::ERROR_ALREADY_EXIST);
            return;
        }

        if ($this->store->storeSkus()->firstWhere('sku_id_origin', $this->input['fobiz_code'])) {
            $this->errors()->add('fobiz_code', static::ERROR_ALREADY_EXIST);
            return;
        }

        $merchantIds = $this->user->merchants()->pluck('merchants.id');

        $productMerchant = ProductMerchant::query()
            ->where('product_id', $this->sku->product_id)
            ->whereIn('merchant_id', $merchantIds)
            ->first();

        if (!$productMerchant) {
            $this->errors()->add('user_merchant', static::ERROR_INVALID);
            return;
        }

        if (
            in_array($this->input['fobiz_code'], $this->insertedSkuKeys)
        ) {
            $this->errors()->add('sku_code', static::ERROR_DUPLICATED);
            return;
        }
    }

    /**
     * @return Sku|null
     */
    public function getSku()
    {
        return $this->sku;
    }
}
