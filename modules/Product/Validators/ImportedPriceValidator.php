<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class ImportedPriceValidator extends Validator
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
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var array
     */
    protected $insertedSkuKeys = [];

    /**
     * ImportedPriceValidator constructor.
     * @param User $user
     * @param array $input
     * @param array $insertedSkuKeys
     */
    public function __construct(User $user, array $input, $insertedSkuKeys = [])
    {
        $this->user = $user;
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
            'merchant_code' => 'required',
            'cost_price' => 'numeric|gte:0',
            'wholesale_price' => 'numeric|gte:0',
            'retail_price' => 'required|numeric|gte:0',
        ];
    }

    protected function customValidate()
    {
        $skuCode = $this->input['sku_code'];
        $merchantCode = $this->input['merchant_code'];

        if (!$this->sku = $this->tenant->skus()->firstWhere('code', $skuCode)) {
            $this->errors()->add('sku_code', static::ERROR_NOT_EXIST);
            return;
        }

        if(!$this->merchant = $this->tenant->merchants()->firstWhere('code', $merchantCode)) {
            $this->errors()->add('merchant_code', static::ERROR_NOT_EXIST);
            return;
        }

        if (empty($this->user->merchants()->firstWhere('code', $merchantCode))) {
            $this->errors()->add('merchant_code', static::ERROR_INVALID);
            return;
        }

        if (empty($this->sku->product->merchants()->firstWhere('code', $merchantCode))) {
            $this->errors()->add('merchant_code', static::ERROR_INVALID);
            return;
        }

        if($this->merchant && !$this->merchant->status) {
            $this->errors()->add('merchant_code', static::ERROR_INVALID);
            return;
        }

        if (
            in_array($this->getSkuKey(), $this->insertedSkuKeys)
        ) {
            $this->errors()->add('sku_merchant', static::ERROR_ALREADY_EXIST);
            return;
        }
    }

    /**
     * @return string
     */
    public function getSkuKey()
    {
        $skuCode = $this->input['sku_code'];
        $merchantCode = $this->input['merchant_code'];
        return "$skuCode - $merchantCode";
    }

    /**
     * @return Sku
     */
    public function sku()
    {
        return $this->sku;
    }

    /**
     * @return Merchant
     */
    public function merchant()
    {
        return $this->merchant;
    }
}
