<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Models\Sale;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderTransaction;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Tenant\Models\Tenant;
use Modules\Warehouse\Models\Warehouse;

class MerchantCreateOrderValidator extends Validator
{
    /**
     * MerchantCreateOrderValidator constructor.
     * @param Merchant $merchant
     * @param array $input
     */
    public function __construct(Merchant $merchant, array $input)
    {
        $this->merchant = $merchant;
        $this->tenant   = $merchant->tenant;
        parent::__construct($input);
    }


    /**
     * @var Tenant
     */
    protected $tenant;

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
     * @var ShippingPartner|null
     */
    protected $shippingPartner;


    /**
     * @var Warehouse|null
     */
    protected $warehouse;

    /**
     * @var Sale
     */
    protected $sale;

    /**
     * @var array
     */
    protected $orderSkus;

    /**
     * @var array
     */
    protected $orderSkuCombos = [];

    /**
     * Tổng tiền khách phải trả
     * @var int
     */
    protected $totalAmount = 0;

    /**
     * Tổng số tiền hàng
     * @var int
     */
    protected $orderAmount = 0;

    /**
     * @var array
     */
    protected $extraServices = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required',
            'freight_bill' => 'string',
            'receiver_name' => 'required',
            'receiver_address' => 'required',
            'receiver_phone' => 'required',
            'warehouse_id' => 'required',
            // 'orderSkus' => 'required',
            'payment_method' => 'in:' . implode(',', OrderTransaction::$methods),
            'payment_type' => 'required|in:' . implode(',', Order::$paymentTypes),
            'payment_amount' => 'numeric|gte:0',
            'discount_amount' => 'numeric|gte:0',
            'total_amount' => 'required|numeric|gte:0',
        ];
    }

    protected function customValidate()
    {
        $code         = data_get($this->input, 'code');
        $validateCode = preg_match("/\s/s", trim($code));
        if ($validateCode) {
            $this->errors()->add('order_code', static::ERROR_INVALID);
            return;
        }
        $nameStore = $this->input('name_store');
        $datas = [];
        $stores = $this->merchant->stores ? : null;
        if ($nameStore && $stores){
            foreach ($stores as $store){
                $datas[] = $store->getNameStore();
            }
            if (!in_array($nameStore, $datas)){
                $this->errors()->add('name_store', static::ERROR_INVALID);
            }
        }

        $country = $this->merchant->getCountry();
        if(!$country instanceof Location) {
            $this->errors()->add('country', static::ERROR_NOT_EXIST);
            return;
        }

        $errors = [];
        if (
            !empty($this->input['shipping_partner_id']) &&
            !$this->shippingPartner = $country->shippingPartners()->firstWhere('shipping_partners.id', $this->input('shipping_partner_id'))
        ) {
            $errors['shipping_partner_code'] = static::ERROR_NOT_EXIST;
        };

        if (
            !empty($this->input['warehouse_id']) &&
            !$this->warehouse = $this->merchant->tenant->warehouses()->firstWhere('warehouses.id', $this->input('warehouse_id'))
        ) {
            $errors['warehouse_id'] = static::ERROR_NOT_EXIST;
        };

        $freightBill = $this->input('freight_bill');
        if ($freightBill) {
            if(empty($this->input['shipping_partner_id']) || !$this->shippingPartner) {
                $errors['shipping_partner_id'] = static::ERROR_REQUIRED;
            } else {
                $orderExist = Order::query()->where(['freight_bill' => $freightBill, 'shipping_partner_id' => $this->shippingPartner->id])->first();
                if ($orderExist) {
                    $errors['freight_bill'] = static::ERROR_EXISTS;
                }
            }
        }
        $code = trim($this->input['code']);
        if (
        $this->merchant->orders()->firstWhere('code', $code)
        ) {
            $errors['code'] = static::ERROR_EXISTS;
        }
        $bankErrors = $this->validateBank();
        if(!empty($bankErrors)) {
            $this->errors()->add('bank_errors', $bankErrors);
        }


        $locationErrors = $this->validateLocation($country);
        if (!empty($locationErrors)) {
            $this->errors()->add('location_errors', $locationErrors);
        }

        $skuErrors = $this->validateOrderSkus();
        if (!empty($skuErrors)) {
            $this->errors()->add('sku_errors', $skuErrors);
        }

        $skuComboErrors = $this->validateOrderSkuCombos();
        if (!empty($skuComboErrors)) {
            $this->errors()->add('sku_combo_errors', $skuComboErrors);
        }

        if (!empty($this->input['created_at_origin'])) {
            $createdAtOrigin = Service::order()->formatDateTime($this->input['created_at_origin']);
            if ($createdAtOrigin->gt(date('Y-m-d 23:59:59'))) {
                $errors['created_at_origin'] = static::ERROR_INVALID;
            }
        }

        $shippingAmount    = isset($this->input['shipping_amount']) ? floatval($this->input['shipping_amount']) : 0;
        $discountAmount    = isset($this->input['discount_amount']) ? floatval($this->input['discount_amount']) : 0;
        $this->totalAmount = $this->totalAmount + $shippingAmount - $discountAmount;

        /**
         * Lưu tổng thanh toán theo giá trị nhập tay nếu có
         */
        $totalAmount = $this->input('total_amount');
        if ($totalAmount) {
            $this->totalAmount = (float)$totalAmount;
        }

        if (isset($this->input['payment_amount']) && floatval($this->input['payment_amount']) > floatval($this->totalAmount)) {
            $errors['payment_amount'] = self::ERROR_GREATER;
        }
        if (!empty($errors)) {
            $this->errors()->add('errors', $errors);
        }
    }

    /**
     * @return array
     */
    protected function validateBank()
    {
        $bankErrors = [];
        if($this->input['payment_type'] == Order::PAYMENT_TYPE_ADVANCE_PAYMENT) {
            if(
                !empty($this->input['payment_method']) &&
                $this->input['payment_method'] == OrderTransaction::METHOD_BANK_TRANSFER &&
                (!empty($this->input['bank_name']) || !empty($this->input['bank_account']))
            ) {
                if(empty($this->input['bank_name'])) {
                    $bankErrors['bank_name'] = static::ERROR_REQUIRED;
                }

                if(empty($this->input['bank_account'])) {
                    $bankErrors['bank_account'] = static::ERROR_REQUIRED;
                }
            }

            if(
                !empty($this->input['payment_method']) ||
                !empty($this->input['payment_amount']) ||
                !empty($this->input['payment_time'])
            ) {
                if(empty($this->input['payment_method'])) {
                    $bankErrors['payment_method'] = static::ERROR_REQUIRED;
                }

                if(empty($this->input['payment_amount'])) {
                    $bankErrors['payment_amount'] = static::ERROR_REQUIRED;
                }

                if(empty($this->input['payment_time'])) {
                    $bankErrors['payment_time'] = static::ERROR_REQUIRED;
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

        if (!empty($this->input['receiver_province_id'])) {
            if (!$this->receiverProvince = Location::query()->firstWhere([
                'id' => $this->input['receiver_province_id'],
                'type' => Location::TYPE_PROVINCE
            ])) {
                $locationErrors['receiver_province_id'] = static::ERROR_NOT_EXIST;
            }

            $countryCode = ($country instanceof Location) ? $country->code : '';
            if ($this->receiverProvince && $this->receiverProvince->parent_code != trim($countryCode)) {
                $locationErrors['receiver_province_id'] = static::ERROR_INVALID;
            }
        }

        if (!empty($this->input['receiver_district_id'])) {
            if (!$this->receiverDistrict = Location::query()->firstWhere([
                'id' => $this->input['receiver_district_id'],
                'type' => Location::TYPE_DISTRICT
            ])) {
                $locationErrors['receiver_district_id'] = static::ERROR_NOT_EXIST;
            }

            if (
                $this->receiverProvince instanceof Location &&
                $this->receiverDistrict &&
                $this->receiverDistrict->parent_code != $this->receiverProvince->code
            ) {
                $locationErrors['receiver_district_id'] = static::ERROR_INVALID;
            }
        }

        if (!empty($this->input['receiver_ward_id'])) {
            if (!$this->receiverWard = Location::query()->firstWhere([
                'id' => $this->input['receiver_ward_id'],
                'type' => Location::TYPE_WARD
            ])) {
                $locationErrors['receiver_ward_id'] = static::ERROR_NOT_EXIST;
            }

            if (
                !$this->receiverDistrict instanceof Location ||
                (
                    $this->receiverWard && $this->receiverWard->parent_code != $this->receiverDistrict->code
                )
            ) {
                $locationErrors['receiver_ward_id'] = static::ERROR_INVALID;
            }
        }

        return $locationErrors;
    }

    /**
     * @return array
     */
    protected function validateOrderSkus()
    {
        $orderSkus   = data_get($this->input, 'orderSkus', []);
        $SkuRequired = ['quantity'];
        $line        = 0;
        $skuErrors   = [];
        $skuIds      = [];

        if ($orderSkus) {
            foreach ($orderSkus as $orderSku) {
                $line++;
                $lineKey = 'line_' . $line;
                foreach ($SkuRequired as $key) {
                    if (!isset($orderSku[$key])) {
                        $skuErrors[$lineKey][self::ERROR_REQUIRED][] = $key;
                        continue;
                    }
                }
    
                if (empty($orderSku['sku_id']) && empty($orderSku['sku_code']) && empty($orderSku['code'])) {
                    $skuErrors[$lineKey][self::ERROR_REQUIRED][] = 'sku_id';
                    continue;
                }
    
                foreach (['discount_amount'] as $key) {
                    if (!isset($orderSku[$key]) || floatval($orderSku[$key]) < 0) {
                        $skuErrors[$lineKey][self::ERROR_INVALID][] = $key;
                    }
                }
    
                if (
                    isset($orderSku['tax']) &&
                    (!is_numeric($orderSku['tax']) || floatval($orderSku['tax']) < 0)
                ) {
                    $skuErrors[$lineKey][self::ERROR_INVALID][] = 'tax';
                }
    
                foreach (['quantity'] as $key) {
                    if (!isset($orderSku[$key]) || !is_numeric($orderSku[$key]) || floatval($orderSku[$key]) <= 0) {
                        $skuErrors[$lineKey][self::ERROR_INVALID][] = $key;
                    }
                }
                $sku   = '';
                $skuId = '';
                if (!empty($orderSku['sku_id'])) {
                    $skuId = intval($orderSku['sku_id']);
                    $sku   = $this->tenant->skus()->firstWhere('id', $skuId);
                }
                if (!$sku instanceof Sku && !empty($orderSku['code'])) {
                    $skuId = trim($orderSku['code']);
                    $sku   = $this->merchant->skus()->firstWhere('code', $skuId);
                }
    
                if (!$sku instanceof Sku) {
                    $skuErrors[$lineKey][self::ERROR_INVALID][] = 'sku_id';
                } else {
                    $productMerchant = ProductMerchant::query()
                        ->where('product_id', $sku->product_id)
                        ->where('merchant_id', $this->merchant->id)
                        ->first();
                    if (!$productMerchant) {
                        $skuErrors[$lineKey][self::ERROR_INVALID][] = 'sku_id';
                    }
                }
    
                if($sku->product->dropship) {
                    $skuErrors[$lineKey]['warning_dropship'][] = $sku->code;
                }
    
                if (in_array($skuId, $skuIds)) {
                    $skuErrors[$lineKey][self::ERROR_ALREADY_EXIST][] = 'sku_id';
                }
    
                if (!empty($skuErrors[$lineKey])) {
                    continue;
                }
    
                $price           = isset($orderSku['price']) ? floatval($orderSku['price']) : null;
                $quantity        = intval($orderSku['quantity']);
                $discount_amount = floatval($orderSku['discount_amount']);
                $tax             = isset($orderSku['tax']) ? floatval($orderSku['tax']) : null;
    
    
                $orderAmount = (float)$price * $quantity;
                $totalAmount = ($orderAmount + ($orderAmount * floatval($tax) * 0.01)) - $discount_amount;
    
                $this->orderSkus[$sku->id] = [
                    'sku_id' => $sku->id,
                    'quantity' => $quantity,
                    'tax' => $tax,
                    'price' => $price,
                    'discount_amount' => $discount_amount,
                    'order_amount' => $orderAmount,
                    'total_amount' => $totalAmount
                ];
                $skuIds[]          = $skuId;
    
                $this->orderAmount += $totalAmount;
                $this->totalAmount += $totalAmount;
            }
        }

        return $skuErrors;
    }

    /**
     * @return array
     */
    protected function validateOrderSkuCombos()
    {
        $orderSkuCombos = data_get($this->input, 'orderSkuCombos', []);

        $skuErrors = [];

        if ($orderSkuCombos) {
            foreach ($orderSkuCombos as $orderSkuCombo) {
                $id       = data_get($orderSkuCombo, 'id', 0);
                $quantity = data_get($orderSkuCombo, 'quantity', 0);
                $price    = data_get($orderSkuCombo, 'price', 0);

                $skuCombo = SkuCombo::find($id);
                if ($skuCombo) {
                    $this->orderSkuCombos[$skuCombo->id] = [
                        'id'       => $skuCombo->id,
                        'quantity' => $quantity,
                        'price'    => $price,
                    ];
                    $this->orderAmount += $quantity * $price;
                    $this->totalAmount += $quantity * $price;
                } else {
                    $skuErrors[][self::ERROR_INVALID] = 'Sku combo id '. $id . ' not found';
                }
            }
        }

        return $skuErrors;
    }

    /**
     * @return array
     */
    public function getExtraServices()
    {
        return $this->extraServices;
    }

    /**
     * @return int
     */
    public function getOrderAmount()
    {
        return $this->orderAmount;
    }


    /**
     * @return int
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @return array
     */
    public function getOrderSkus()
    {
        return $this->orderSkus;
    }

    /**
     * @return array
     */
    public function getOrderSkuCombos()
    {
        return $this->orderSkuCombos;
    }

    public function getMerchant()
    {
        return $this->merchant;
    }

    /**
     * @return Sale
     */
    public function getSale()
    {
        return $this->sale;
    }

    /**
     * @return Location|null
     */
    public function getReceiverCountry()
    {
        return $this->receiverCountry;
    }


    /**
     * @return Location|null
     */
    public function getReceiverProvince()
    {
        return $this->receiverProvince;
    }


    /**
     * @return Location|null
     */
    public function getReceiverDistrict()
    {
        return $this->receiverDistrict;
    }


    /**
     * @return Location|null
     */
    public function getReceiverWard()
    {
        return $this->receiverWard;
    }

    /**
     * @return ShippingPartner|null
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }
}
