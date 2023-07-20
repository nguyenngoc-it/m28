<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderTransaction;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Store\Models\StoreSku;

class CreateOrderFromApiValidator extends Validator
{
    /**
     * CreateOrderValidator constructor.
     * @param array $input
     */
    public function __construct(array $input)
    {
        parent::__construct($input);
    }

    /**
     * @var Merchant
     */
    protected $merchant;


    /**
     * @var Location|null
     */
    protected $receiverCountry;

    /**
     * @var Location|null
     */
    protected $receiverProvince;

    /**
     * @var Location|null
     */
    protected $receiverDistrict;

    /**
     * @var Location|null
     */
    protected $receiverWard;


    /**
     * @return array
     */
    public function rules()
    {
        return [
            'merchant_code' => 'required',
            'receiver_name' => 'required',
            'receiver_address' => 'required',
            'receiver_phone' => 'required',
            'receiver_country_code' => 'required',
            'receiver_province_code' => 'required',
            'receiver_district_code' => 'required',
            'skus' => 'array',
            'sku_combos' => 'array',
            'payment' => '',
            'payment.payment_method' => 'in:' . implode(',', OrderTransaction::$methods),
            'payment.payment_type' => 'in:' . implode(',', Order::$paymentTypes),
            'payment.payment_amount' => 'numeric|gte:0',
            'payment.discount_amount' => 'numeric|gte:0',
        ];
    }

    protected function customValidate()
    {
        $refCode = data_get($this->input, 'ref_code');
        $order = Order::query()->firstWhere('code', $refCode);
        if ($order){
            $this->errors()->add('order', static::ERROR_EXISTS);
            return;
        }



        $merchantErrors = $this->validateMerchant();
        if (!empty($merchantErrors)) {
            $this->errors()->add('merchant_errors', $merchantErrors);
        }

        $country = Location::query()->firstWhere([
            'code' => $this->input['receiver_country_code'],
            'type' => Location::TYPE_COUNTRY
        ]);
        if (!$country instanceof Location) {
            $this->errors()->add('country', static::ERROR_NOT_EXIST);
            return;
        }

        $locationErrors = $this->validateLocation($country);
        if (!empty($locationErrors)) {
            $this->errors()->add('location_errors', $locationErrors);
        }

        $itemsErrors = $this->validateItems();
        if (!empty($itemsErrors)) {
            $this->errors()->add('item_errors', $itemsErrors);
        }

        if (!$this->validateOrderTotalAmount()) {
            $this->errors()->add('order_total_amount', [self::ERROR_INVALID => "ORDER TOTAL AMOUNT MUST > 0"]);
            return;
        }

        $paymentAmount = data_get($this->input, 'payment.payment_amount');
        $totalAmount   = data_get($this->input, 'total_amount');
        if (!empty($paymentAmount) && !empty($totalAmount)){
            if ($paymentAmount > $totalAmount){
                $this->errors()->add('payment_amount', static::ERROR_INVALID);
                return;
            }

        }

        if (!$this->validateOrderDiscountAmount()) {
            $this->errors()->add('order_discount_amount', [self::ERROR_INVALID => "ORDER DISCOUNT AMOUNT MUST > 0"]);
            return;
        }


        $skuItemsErrors = $this->validateSkuItems();
        if (!empty($skuItemsErrors)) {
            $this->errors()->add('sku_item_errors', $skuItemsErrors);
        }
        $bankErrors = $this->validateBank();
        if (!empty($bankErrors)) {
            $this->errors()->add('bank_errors', $bankErrors);
        }

        $skuComboItemsErrors = $this->validateSkuComboItems();
        if (!empty($skuComboItemsErrors)) {
            $this->errors()->add('sku_combo_item_errors', $skuComboItemsErrors);
        }

        if (!empty($locationErrors) || !empty($skuItemsErrors)) {
            return;
        }
    }

    /**
     * @return array
     */
    protected function validateBank()
    {
        $bankErrors = [];
        $payment = data_get($this->input, 'payment');
        if ($payment){
            if ($this->input['payment']['payment_type'] == Order::PAYMENT_TYPE_ADVANCE_PAYMENT) {
                if (
                    !empty($this->input['payment']['payment_method']) &&
                    $this->input['payment']['payment_method'] == OrderTransaction::METHOD_BANK_TRANSFER &&
                    (!empty($this->input['payment']['bank_name']) || !empty($this->input['payment']['bank_account']))
                ) {
                    if (empty($this->input['payment']['bank_name'])) {
                        $bankErrors['payment']['bank_name'] = static::ERROR_REQUIRED;
                    }

                    if (empty($this->input['payment']['bank_account'])) {
                        $bankErrors['payment']['bank_account'] = static::ERROR_REQUIRED;
                    }
                }

                if (
                    !empty($this->input['payment']['payment_method']) ||
                    !empty($this->input['payment']['payment_amount']) ||
                    !empty($this->input['payment']['payment_time'])
                ) {
                    if (empty($this->input['payment']['payment_method'])) {
                        $bankErrors['payment']['payment_method'] = static::ERROR_REQUIRED;
                    }

                    if (empty($this->input['payment']['payment_amount'])) {
                        $bankErrors['payment']['payment_amount'] = static::ERROR_REQUIRED;
                    }

                    if (empty($this->input['payment']['payment_time'])) {
                        $bankErrors['payment']['payment_time'] = static::ERROR_REQUIRED;
                    }
                }
            }
        }
        return $bankErrors;
    }

    /**
     * @param Location $country
     * @return array
     */
    protected function validateLocation(Location $country)
    {
        $locationErrors = [];

        if (!empty($this->input['receiver_province_code'])) {
            if (!$this->receiverProvince = Location::query()->firstWhere([
                'code' => $this->input['receiver_province_code'],
                'type' => Location::TYPE_PROVINCE
            ])) {
                $locationErrors['receiver_province_code'] = static::ERROR_NOT_EXIST;
            }

            $countryCode = ($country instanceof Location) ? $country->code : '';
            if ($this->receiverProvince && $this->receiverProvince->parent_code != trim($countryCode)) {
                $locationErrors['receiver_province_code'] = static::ERROR_INVALID;
            }
        }

        if (!empty($this->input['receiver_district_code'])) {
            if (!$this->receiverDistrict = Location::query()->firstWhere([
                'code' => $this->input['receiver_district_code'],
                'type' => Location::TYPE_DISTRICT
            ])) {
                $locationErrors['receiver_district_code'] = static::ERROR_NOT_EXIST;
            }

            if (
                $this->receiverProvince instanceof Location &&
                $this->receiverDistrict &&
                $this->receiverDistrict->parent_code != $this->receiverProvince->code
            ) {
                $locationErrors['receiver_district_code'] = static::ERROR_INVALID;
            }
        }

        if (!empty($this->input['receiver_ward_code'])) {
            if (!$this->receiverWard = Location::query()->firstWhere([
                'code' => $this->input['receiver_ward_code'],
                'type' => Location::TYPE_WARD
            ])) {
                $locationErrors['receiver_ward_code'] = static::ERROR_NOT_EXIST;
            }

            if (
                !$this->receiverDistrict instanceof Location ||
                (
                    $this->receiverWard && $this->receiverWard->parent_code != $this->receiverDistrict->code
                )
            ) {
                $locationErrors['receiver_ward_code'] = static::ERROR_INVALID;
            }
        }

        return $locationErrors;
    }

    /**
     * Validate Merchant
     *
     * @return array
     */
    protected function validateMerchant()
    {
        $merchantErrors = [];
        $merchantCode   = data_get($this->input, 'merchant_code', '');
        $this->merchant = Merchant::where('code', $merchantCode)->first();
        if (!$this->merchant) {
            $merchantErrors['merchant'] = static::ERROR_NOT_EXIST;
        }
        return $merchantErrors;
    }

    /**
     * Validate items
     *
     * @return array
     */
    protected function validateItems()
    {
        $skus       = data_get($this->input, 'skus', []);
        $skuCombos  = data_get($this->input, 'sku_combos', []);
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
        $skus          = data_get($this->input, 'skus', []);
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
                $skuCode  = $sku['code'];
                $skuCheck = Sku::select('skus.*')
                    ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                    ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                    ->where(function ($query) use ($skuCode) {
                        return $query->where('store_skus.code', $skuCode)
                            ->orWhere('skus.code', $skuCode);
                    })
                    ->where(function ($query) {
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
        $skuCombos     = data_get($this->input, 'sku_combos', []);
        foreach ($skuCombos as $skuCombo) {
            // Check Sku Đã tồn tại trên hệ thống chưa
            $code     = data_get($skuCombo, 'code', '');
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

    /**
     * Validate thông tin tổng thanh toán của đơn
     *
     * @return boolean
     */
    protected function validateOrderTotalAmount()
    {
        $totalAmount = data_get($this->input, 'total_amount');
        if (!is_numeric($totalAmount)) {
            $this->errors()->add('total_amount', ['TOTAL AMOUNT MUST IS NUMERIC']);
        }
        if ($totalAmount < 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Validate thông tin tổng thanh toán của đơn
     *
     * @return boolean
     */
    protected function validateOrderDiscountAmount()
    {
        $discountAmount = data_get($this->input, 'discount_amount', NULL);

        if ($discountAmount == NULL) {
            $discountAmount = 0;
        }

        if (!is_numeric($discountAmount)) {
            $this->errors()->add('discount_amount', ['DISCOUNT AMOUNT MUST IS NUMERIC']);
        }
        if ($discountAmount < 0) {
            return false;
        } else {
            return true;
        }
    }
}
