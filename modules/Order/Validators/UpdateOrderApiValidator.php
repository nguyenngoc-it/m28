<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Store\Models\StoreSku;

class UpdateOrderApiValidator extends Validator
{
    /**
     * UpdateOrderValidator constructor.
     * @param Order $order
     * @param array $input
     */
    public function __construct(Order $order, array $input)
    {
        $this->order = $order;
        parent::__construct($input);
    }

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Merchant
     */
    protected $merchant;


    /**
     * @return array
     */
    public function rules()
    {
        return [
            'receiver_name'          => 'string',
            'receiver_phone'         => 'string',
            'receiver_address'       => 'string',
            'receiver_country_code'  => 'string',
            'receiver_province_code' => 'string',
            'receiver_district_code' => 'string',
            'receiver_ward_code'     => 'string',
            'product_quantity'       => 'integer',
            'total_amount'           => 'numeric',
            'discount_amount'        => 'numeric',
            'skus'                   => 'array',
            'sku_combos'             => 'array',
        ];
    }

    protected function customValidate()
    {
        $merchantErrors = $this->validateMerchant();
        if(!empty($merchantErrors)) {
            $this->errors()->add('merchant_errors', $merchantErrors);
        }

        $itemsErrors = $this->validateItems();
        if(!empty($itemsErrors)) {
            $this->errors()->add('item_errors', $itemsErrors);
        }

        if (!$this->validateOrderStatus()) {
            $this->errors()->add('order_status', [self::ERROR_INVALID => "{$this->order->status} STATUS NOT ALLOW EDIT"]);
            return;
        }

        if (!$this->validateOrderMarketType()) {
            $this->errors()->add('order_marketplace_type', [self::ERROR_INVALID => "{$this->order->marketplace_code} MARKETPLACE NOT ALLOW EDIT"]);
            return;
        }

        if (!$this->validateOrderInspected()) {
            $this->errors()->add('order_inspected', [self::ERROR_INVALID => "ORDER INSPECTED NOT ALLOW EDIT"]);
            return;
        }

        if (!$this->validateOrderTotalAmount()) {
            $this->errors()->add('order_total_amount', [self::ERROR_INVALID => "ORDER TOTAL AMOUNT MUST >= 0"]);
            return;
        }

        if (!$this->validateOrderDiscountAmount()) {
            $this->errors()->add('order_discount_amount', [self::ERROR_INVALID => "ORDER DISCOUNT AMOUNT MUST >= 0"]);
            return;
        }

        $skuItemsErrors = $this->validateSkuItems();
        if(!empty($skuItemsErrors)) {
            $this->errors()->add('sku_item_errors', $skuItemsErrors);
        }

        $skuComboItemsErrors = $this->validateSkuComboItems();
        if(!empty($skuComboItemsErrors)) {
            $this->errors()->add('sku_combo_item_errors', $skuComboItemsErrors);
        }

        $dataValidateLocations = $this->validateOrderLocation();
        if ($dataValidateLocations) {
            foreach ($dataValidateLocations as $dataValidateLocation) {
                $this->errors()->add('order_location_' . $dataValidateLocation['type'], [self::ERROR_INVALID => $dataValidateLocation['message']]);
                return;
            }
        }
        
    }


    /**
     * Validate Merchant
     *
     * @return array
     */
    protected function validateMerchant()
    {
        $merchantErrors = [];
        $merchantCode = data_get($this->input, 'merchant_code', '');
        $this->merchant = Merchant::where('code', $merchantCode)->first();
        if (!$this->merchant) {
            $merchantErrors['merchant'] = static::ERROR_NOT_EXIST;
        }
        return $merchantErrors;
    }

    /**
     * Validate trạng thái đơn hàng có cho phép sửa hay không
     *
     * @return boolean
     */
    protected function validateOrderStatus()
    {
        $orderStatusValidList = [
            Order::STATUS_WAITING_PROCESSING,
            Order::STATUS_WAITING_CONFIRM,
            Order::STATUS_WAITING_INSPECTION,
        ];

        $check = in_array($this->order->status, $orderStatusValidList);
        return $check;
    }

    /**
     * Validate kiểu đơn hàng
     *
     * @return boolean
     */
    protected function validateOrderMarketType()
    {
        $orderMarketTypeValidList = [
            Marketplace::CODE_VELAONE
        ];

        $check = in_array($this->order->marketplace_code, $orderMarketTypeValidList);
        return $check;
    }

    /**
     * Validate đơn hàng đã được chọn vị trí hay chưa
     *
     * @return boolean
     */
    protected function validateOrderInspected()
    {
        // Check thông tin sảnn phẩm của đơn có bị sửa hay không
        $diff = true;
        if ($this->order->inspected && $this->order->status != Order::STATUS_WAITING_CONFIRM) {
            $orderSkus      = $this->order->skus->pluck(['code'])->all();
            $orderSkusInput = collect(data_get($this->input, 'skus', []))->pluck(['code'])->all();

            $diff = array_diff($orderSkusInput, $orderSkus) === array_diff($orderSkus, $orderSkusInput);

            if ($this->order->marketplace_code == Marketplace::CODE_VELAONE) {
                $diff = true;
                $orderSkuIds = $this->order->skus->pluck(['id'])->all();
                foreach ($orderSkusInput as $orderSkuCode) {
                    foreach ($orderSkuIds as $skuId) {
                        $storeSku = StoreSku::query()
                                            ->where('sku_id', $skuId)
                                            ->where('code', $orderSkuCode)
                                            ->first(); 
                        if (!$storeSku) {
                            $diff = false;
                        } else {
                            //kiểm tra thay đổi quantity
                            $orderSku = $this->order->orderSkus->first(function ($value) use ($skuId) {
                                return $value->sku_id == $skuId;
                            });
                            $orderSkuInput = collect(data_get($this->input, 'skus', []))->first(function ($value) use ($orderSkuCode) {
                                return $value['code'] == $orderSkuCode;
                            });

                            if ($orderSku && $orderSkuInput) {
                                if ($orderSku->quantity != $orderSkuInput['quantity']) {
                                    $diff = false;
                                }
                            }
                        }
                    }
                }
            }

        }
        return $diff;
    }

    /**
     * Validate thông tin tổng thanh toán của đơn
     *
     * @return boolean
     */
    protected function validateOrderTotalAmount()
    {
        $totalAmount = data_get($this->input, 'total_amount', 0);
        if ($totalAmount < 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Validate thông tin tổng discount đơn
     *
     * @return boolean
     */
    protected function validateOrderDiscountAmount()
    {
        $discountAmount = data_get($this->input, 'discount_amount', 0);
        if ($discountAmount < 0) {
            return false;
        } else {
            return true;
        }
    }



    /**
     * Validate thông tin tổng thanh toán của đơn
     *
     * @return array
     */
    protected function validateOrderLocation()
    {
        $receiverCountryCode  = data_get($this->input, 'receiver_country_code');
        $receiverProvinceCode = data_get($this->input, 'receiver_province_code');
        $receiverDistrictCode = data_get($this->input, 'receiver_district_code');
        $receiverWardCode     = data_get($this->input, 'receiver_ward_code');

        $dataLocations = [];
        
        if ($receiverCountryCode) {
            $receiverCountry = Location::where('code', $receiverCountryCode)
                                        ->where('type', Location::TYPE_COUNTRY)
                                        ->first();

            if (!$receiverCountry) {
                $dataLocations[] = ['type' => 'country', 'message' => "COUNTRY CODE: " . $receiverCountryCode . " NOT EXIST"];
            } else {

                if ($receiverProvinceCode) {
                    $receiverProvince = Location::where('code', $receiverProvinceCode)    
                                                    ->where('type', Location::TYPE_PROVINCE)
                                                    ->where('parent_code', $receiverCountryCode)
                                                    ->first();
                                                
                    if (!$receiverProvince) {
                        $dataLocations[] = ['type' => 'province', 'message' => "PROVINCE CODE: " . $receiverProvinceCode . " NOT EXIST WITH COUNTRY CODE: " . $receiverCountryCode];
                    } else {

                        if ($receiverDistrictCode) {
                            $receiverDistrict = Location::where('code', $receiverDistrictCode)    
                                                        ->where('type', Location::TYPE_DISTRICT)
                                                        ->where('parent_code', $receiverProvinceCode)
                                                        ->first();
                            
                            if (!$receiverDistrict) {
                                $dataLocations[] = ['type' => 'district', 'message' => "DISTRICT CODE: " . $receiverDistrictCode . " NOT EXIST WITH PROVINCE CODE: " . $receiverProvinceCode];
                            } else {
                                
                                if ($receiverWardCode) {
                                    $receiverWard = Location::where('code', $receiverWardCode)    
                                                                ->where('type', Location::TYPE_WARD)
                                                                ->where('parent_code', $receiverDistrictCode)
                                                                ->first();
                                    
                                    if (!$receiverWard) {
                                        $dataLocations[] = ['type' => 'ward', 'message' => "WARD CODE: " . $receiverWardCode . " NOT EXIST WITH DISTRICT CODE: " . $receiverDistrictCode];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $dataLocations;

    }

    /**
     * Validate items
     *
     * @return array
     */
    protected function validateItems()
    {
        $skus      = data_get($this->input, 'skus', []);
        $skuCombos = data_get($this->input, 'sku_combos', []);
        $itemErrors = [];
        if (!$skus && !$skuCombos) {
            $itemErrors['items'] = "MUST HAVE WITH CREATE ORDER";
        }
        return $itemErrors;
    }

    /**
     * Validate sku items
     *
     * @return array
     */
    protected function validateSkuItems()
    {
        $skuItemErrors = [];
        $skus = data_get($this->input, 'skus', []);
        foreach ($skus as $sku) {
            // Check Sku Đã tồn tại trên hệ thống chưa
            $quantity = data_get($sku, 'quantity', 0);
            $price    = data_get($sku, 'price', 0);

            if (!is_int($quantity)) {
                $skuItemErrors['sku_code_' . $sku['code']] = "SKU QUANTITY MUST NUMERIC";
            } else {
                if ($quantity <= 0) {
                    $skuItemErrors['sku_code_' . $sku['code']] = "SKU QUANTITY MUST > 0";
                }
            }

            if (!is_numeric($price)) {
                $skuItemErrors['sku_code_' . $sku['code']] = "SKU PRICE MUST NUMERIC";
            } else {
                if ($price < 0) {
                    $skuItemErrors['sku_code_' . $sku['code']] = "SKU PRICE MUST >= 0";
                }   
            }

            if ($sku['code']) {
                $skuCode = $sku['code'];
                $skuCheck = Sku::select('skus.*')
                            ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                            ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                            ->where(function($query) use ($skuCode){
                                return $query->where('store_skus.code', $skuCode)
                                                ->orWhere('skus.code', $skuCode);
                            })
                            ->where(function($query) {
                                return $query->where('skus.merchant_id', $this->merchant->id)
                                                ->orWhere('product_merchants.merchant_id', $this->merchant->id);
                            })
                            ->where('skus.status', Sku::STATUS_ON_SELL)
                            ->first();
                if (!$skuCheck) {
                    $skuItemErrors['sku_code_' . $sku['code']] = static::ERROR_NOT_EXIST;
                }
            }
        }
        return $skuItemErrors;
    }


    /**
     * Validate sku items
     *
     * @return array
     */
    protected function validateSkuComboItems()
    {
        $skuItemErrors = [];
        $skuCombos = data_get($this->input, 'sku_combos', []);
        foreach ($skuCombos as $skuCombo) {
            // Check Sku Đã tồn tại trên hệ thống chưa
            $code = data_get($skuCombo, 'code', '');
            $quantity = data_get($skuCombo, 'quantity', 0);
            $price    = data_get($skuCombo, 'price', 0);

            if (!is_int($quantity)) {
                $skuItemErrors['sku_combo_code_' . $code] = "SKU COMBO QUANTITY MUST NUMERIC";
            } else {
                if ($quantity <= 0) {
                    $skuItemErrors['sku_combo_code_' . $code] = "SKU COMBO QUANTITY MUST > 0";
                }
            }

            if (!is_numeric($price)) {
                $skuItemErrors['sku_combo_code_' . $code] = "SKU COMBO PRICE MUST NUMERIC";
            } else {
                if ($price < 0) {
                    $skuItemErrors['sku_combo_code_' . $code] = "SKU COMBO PRICE MUST >= 0";
                }
            }

            if ($code) {
                $skuComboCheck = SkuCombo::where('code', $code)
                                            ->where('merchant_id', $this->merchant->id)
                                            ->where('status', SkuCombo::STATUS_ON_SELL)
                                            ->first();
                if (!$skuComboCheck) {
                    $skuItemErrors['sku_combo_code_' . $code] = static::ERROR_NOT_EXIST;
                }
            }
        }
        return $skuItemErrors;
    }

    
}
