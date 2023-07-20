<?php

namespace Modules\Marketplace\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Marketplace\Services\OAuthConnectable;
use Modules\Marketplace\Services\MarketplaceInterface;
use Modules\Warehouse\Models\Warehouse;

class MakeOAuthUrlValidator extends Validator
{
    /**
     * @var MarketplaceInterface|OAuthConnectable
     */
    protected $marketplace;

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'tenant_id' => 'required',
            'merchant_id' => 'required|int',
            'marketplace_code' => 'required|string',
            'warehouse_id' => 'required',
            'domain' => 'required'
        ];
    }

    public function customValidate()
    {
        $warehouseId = $this->input('warehouse_id');
        if (!$this->marketplace = Service::marketplace()->marketplace($this->input('marketplace_code'))) {
            $this->errors()->add('marketplace_code', static::ERROR_EXISTS);
        }

        if (!$this->marketplace instanceof OAuthConnectable) {
            $this->errors()->add('marketplace_code', static::ERROR_INVALID);
        }

        if (!$this->domain = $this->input('domain')) {
            $this->errors()->add('domain', static::ERROR_EXISTS);
        }

        $this->merchant = Merchant::query()->firstWhere([
            'id' => $this->input('merchant_id'),
            'tenant_id' => $this->input('tenant_id'),
        ]);

        if (!$this->merchant) {
            $this->errors()->add('merchant_id', static::ERROR_EXISTS);
            return;
        }

        if (!in_array($warehouseId, $this->merchant->location->warehouses->pluck('id')->all())) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
        }
    }

    /**
     * @return MarketplaceInterface|OAuthConnectable
     */
    public function getMarketplace()
    {
        return $this->marketplace;
    }

    /**
     * @return Merchant
     */
    public function getMerchant()
    {
        return $this->merchant;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse(): Warehouse
    {
        return $this->warehouse;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }
}
