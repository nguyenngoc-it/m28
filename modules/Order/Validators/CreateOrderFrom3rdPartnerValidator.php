<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;

class CreateOrderFrom3rdPartnerValidator extends Validator
{

    /**
     * CreateOrderFrom3rdPartnerValidator constructor.
     * @param array $input
     */
    public function __construct(array $input)
    {
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required',
            'items' => 'required|array'
        ];
    }

    protected function customValidate()
    {
        if (!$this->validateOrderItems()) {
            $this->errors()->add('items', "ITEMS MUST REQUIRE");
            return;
        }
        
        if (!$this->validateMerchantOrderItems()) {
            $this->errors()->add('item_not_exist', "ITEMS DON'T EXIST WITH THIS MERCHANT");
            return;
        }
    }

    protected function validateOrderItems()
    {
        $orderItems = data_get($this->input, 'items', []);
        if (count($orderItems) == 0) {
            return false;
        } else {
            return true;
        }
    }

    protected function validateMerchantOrderItems()
    {
        $orderItems = data_get($this->input, 'items', []);
        $storeId    = data_get($this->input, 'store_id', 0);
        if ($orderItems && $storeId) {
            $skuIds = [];
            foreach ($orderItems as $orderItem) {
                $skuIds[] = data_get($orderItem, 'sku_id', 0);
                $skuIdOrigin = data_get($orderItem, 'id_origin', 0);
                if ($skuIdOrigin) {
                    return true;
                }
            }
            // $storeSku = StoreSku::where('store_id', $storeId)->whereIn('sku_id', $skuIds);
            // if (count($orderItems) != $storeSku->count()) {
            //     return false;
            // }
        }
        return true;
    }
}
