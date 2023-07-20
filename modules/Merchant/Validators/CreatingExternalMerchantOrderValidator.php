<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Models\Sale;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Warehouse\Models\Warehouse;

class CreatingExternalMerchantOrderValidator extends Validator
{
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
            'merchant_code' => 'required',
            'code' => 'required',
            'receiver_name' => 'required',
            'receiver_address' => 'required',
            'receiver_phone' => 'required',
            'warehouse_code' => 'required',
            'products' => 'required',
            'freight_bill' => 'string',
            'discount_amount' => 'numeric|gte:0',
            'total_amount' => 'numeric|gte:0',
            'description',
            'shipping_partner_code',
            'receiver_province_code',
            'receiver_district_code',
            'receiver_ward_code'
        ];
    }

    /**
     * @return Warehouse|null
     */
    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    protected function customValidate()
    {
        $merchantCode   = trim($this->input('merchant_code'));
        $this->merchant = Merchant::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'code' => $merchantCode,
            'creator_id' => $this->user->id
        ])->first();
        if (empty($this->merchant)) {
            $this->errors()->add('merchant_code', static::ERROR_EXISTS);
            return;
        }
        $country = $this->merchant->getCountry();
        if (!$country instanceof Location) {
            $this->errors()->add('country', static::ERROR_EXISTS);
            return;
        }
        $warehouseCode   = $this->input('warehouse_code');
        $this->warehouse = $this->merchant->tenant->warehouses()->firstWhere('warehouses.code', $warehouseCode);
        if (empty($this->warehouse)) {
            $this->errors()->add('warehouse_code', static::ERROR_EXISTS);
            return;
        }
        $shippingPartnerCode = $this->input('shipping_partner_code');
        if ($shippingPartnerCode) {
            $this->shippingPartner = $country->shippingPartners()->firstWhere('shipping_partners.code', $shippingPartnerCode);
            if (empty($this->shippingPartner)) {
                $this->errors()->add('shipping_partner_code', static::ERROR_EXISTS);
                return;
            }
        }
        $freightBill = $this->input('freight_bill');
        if ($freightBill && empty($shippingPartnerCode)) {
            $this->errors()->add('shipping_partner_code', static::ERROR_REQUIRED);
            return;
        }
        if ($freightBill) {
            $orderExist = Order::query()->where(['freight_bill' => $freightBill, 'shipping_partner_id' => $this->shippingPartner->id])->first();
            if ($orderExist) {
                $this->errors()->add('freight_bill', static::ERROR_ALREADY_EXIST);
                return;
            }
        }
        $code = trim($this->input['code']);
        if ($this->merchant->orders()->firstWhere('code', $code)) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }
        $locationErrors = $this->validateLocation($country);
        if (!empty($locationErrors)) {
            $this->errors()->add('location_errors', $locationErrors);
            return;
        }

        $productErrors = $this->validateProducts();
        if (!empty($productErrors)) {
            $this->errors()->add('product_errors', $productErrors);
            return;
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

        if (!empty($errors)) {
            $this->errors()->add('errors', $errors);
            return;
        }
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
                $locationErrors['receiver_province_code'] = static::ERROR_EXISTS;
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
                $locationErrors['receiver_district_code'] = static::ERROR_EXISTS;
            }

            if (
                $this->receiverProvince instanceof Location &&
                $this->receiverDistrict &&
                $this->receiverDistrict->parent_code != $this->receiverProvince->code
            ) {
                $locationErrors['receiver_district_id'] = static::ERROR_INVALID;
            }
        }

        if (!empty($this->input['receiver_ward_code'])) {
            if (!$this->receiverWard = Location::query()->firstWhere([
                'code' => $this->input['receiver_ward_code'],
                'type' => Location::TYPE_WARD
            ])) {
                $locationErrors['receiver_ward_code'] = static::ERROR_EXISTS;
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
     * @return array
     */
    protected function validateProducts()
    {
        $orderSkus   = $this->input['products'];
        $SkuRequired = ['quantity'];
        $line        = 0;
        $skuErrors   = [];
        foreach ($orderSkus as $orderSku) {
            $line++;
            $lineKey = 'line_' . $line;
            foreach ($SkuRequired as $key) {
                if (!isset($orderSku[$key])) {
                    $skuErrors[$lineKey][self::ERROR_REQUIRED][] = $key;
                    continue;
                }
            }
            foreach (['discount_amount'] as $key) {
                if (!isset($orderSku[$key]) || floatval($orderSku[$key]) < 0) {
                    $skuErrors[$lineKey][self::ERROR_INVALID][] = $key;
                    continue;
                }
            }

            foreach (['quantity'] as $key) {
                if (!isset($orderSku[$key]) || !is_numeric($orderSku[$key]) || floatval($orderSku[$key]) <= 0) {
                    $skuErrors[$lineKey][self::ERROR_INVALID][] = $key;
                    continue;
                }
            }

            if (empty($orderSku['code'])) {
                $skuErrors[$lineKey][self::ERROR_REQUIRED][] = 'code';
                continue;
            }
            $sku     = null;
            $skuCode = $orderSku['code'];
            $sku     = $this->merchant->skus()->firstWhere('code', $skuCode);
            if (!$sku instanceof Sku) {
                $skuErrors[$lineKey][self::ERROR_INVALID][] = 'code';
                continue;
            }
            if ($sku->product->dropship) {
                $skuErrors[$lineKey]['warning_dropship'][] = $sku->code;
            }
            if (!empty($skuErrors[$lineKey])) {
                continue;
            }

            $price             = isset($orderSku['price']) ? floatval($orderSku['price']) : null;
            $quantity          = intval($orderSku['quantity']);
            $discount_amount   = floatval($orderSku['discount_amount']);
            $orderAmount       = (float)$price * $quantity;
            $totalAmount       = $orderAmount - $discount_amount;
            $this->orderSkus[] = [
                'sku_id' => $sku->id,
                'quantity' => $quantity,
                'price' => $price,
                'discount_amount' => $discount_amount,
                'order_amount' => $orderAmount,
                'total_amount' => $totalAmount
            ];

            $this->orderAmount += $totalAmount;
            $this->totalAmount += $totalAmount;
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
