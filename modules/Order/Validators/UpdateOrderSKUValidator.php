<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\Store\Models\Store;
use Modules\User\Models\User;

class UpdateOrderSKUValidator extends Validator
{
    /**
     * UpdateOrderSKUValidator constructor.
     * @param Order $order
     * @param array $input
     * @param User $creator
     */
    public function __construct(Order $order, array $input, User $creator)
    {
        $this->order = $order;
        $this->creator = $creator;
        parent::__construct($input);
    }

    /**
     * @var Order
     */
    private $order;

    /**
     * @var User
     */
    private $creator;

    /**
     * @var array
     */
    private $orderSkus = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'orderSkus' => 'required'
        ];
    }

    protected function customValidate()
    {
        if(!$this->order->canUpdateOrderSKU()) {
            $this->errors()->add('order_status', self::ERROR_INVALID);
            return;
        }

        $orderSkus = $this->input['orderSkus'];
        $SkuRequired = ['sku_code', 'quantity'];
        foreach ($orderSkus as $orderSku) {
            foreach ($SkuRequired as $key) {
                if(!isset($orderSku[$key])) {
                    $this->errors()->add('sku_'.$key, self::ERROR_REQUIRED);
                    return;
                }
            }

            if (!is_numeric($orderSku['quantity']) || intval($orderSku['quantity']) <= 0) {
                $this->errors()->add('sku_quantity', self::ERROR_INVALID);
                return;
            }

            $sku = $this->mapExternalSkuCode(trim($orderSku['sku_code']));
            if(!$sku instanceof Sku) {
                $this->errors()->add('sku_code', self::ERROR_INVALID);
                return;
            }

            $skuId = $sku->id;
            if(isset($this->orderSkus[$skuId])) { //merge sku trùng id
                $this->orderSkus[$skuId]['quantity'] += intval($orderSku['quantity']);
            } else {
                $this->orderSkus[$skuId] = ['sku_code' => $sku->code, 'quantity' => intval($orderSku['quantity'])];
            }
        }

        if(empty($this->orderSkus)) {
            $this->errors()->add('orderSkus', self::ERROR_INVALID);
            return;
        }
    }


    /**
     * @return string|null
     */
    protected function getMarketplaceCode()
    {
        return $this->creator->username === User::USERNAME_FOBIZ ? Marketplace::CODE_FOBIZ : null;
    }

    /**
     * @param $externalSkuCode
     * @return mixed|\Modules\Product\Models\Sku|null
     */
    protected function mapExternalSkuCode($externalSkuCode)
    {
        $marketplaceCode = $this->getMarketplaceCode();
        $store = Store::query()->firstWhere([
            'tenant_id' => $this->order->tenant_id,
            'marketplace_code' => $marketplaceCode
        ]);
        if(!$store instanceof Store){
            return null;
        }
        $storeSku = $store->storeSkus()->firstWhere('code', $externalSkuCode);
        return ($storeSku) ? $storeSku->sku : null;
    }


    /**
     * @return array
     */
    public function getOrderSkus()
    {
        return $this->orderSkus;
    }
}
