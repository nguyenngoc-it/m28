<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Models\Sale;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderTransaction;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;

class CreateOrderValidator extends Validator
{
    /**
     * CreateOrderValidator constructor.
     * @param Tenant $tenant
     * @param array $input
     */
    public function __construct(Tenant $tenant, array $input)
    {
        $this->tenant = $tenant;
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
            'merchant_id' => 'required',
            'code' => 'required',
            'receiver_name' => 'required',
            'receiver_address' => 'required',
            'receiver_phone' => 'required',
            'orderSkus' => 'required',
            'payment_method' => 'in:' . implode(',', OrderTransaction::$methods),
            'payment_type' => 'required|in:' . implode(',', Order::$paymentTypes),
            'payment_amount' => 'numeric|gte:0',
            'discount_amount' => 'numeric|gte:0',
            'cod' => 'numeric|gte:0'
        ];
    }

    protected function customValidate()
    {
        $code         = data_get($this->input, 'code');
        $validateCode = preg_match("/\s/s", trim($code));
        if ($validateCode) {
            $this->errors()->add('code', static::ERROR_INVALID);
            return;
        }
        if (
            !$this->merchant = $this->tenant->merchants()->firstWhere('id', $this->input('merchant_id'))
        ) {
            $this->errors()->add('merchant_id', static::ERROR_NOT_EXIST);
            return;
        }

        if (!$this->merchant->status) {
            $this->errors()->add('merchant_id', static::ERROR_NOT_EXIST);
            return;
        }
        $country = $this->merchant->getCountry();
        if (!$country instanceof Location) {
            $this->errors()->add('country', static::ERROR_NOT_EXIST);
            return;
        }

        if (
            !empty($this->input['shipping_partner_id']) &&
            !$this->shippingPartner = $country->shippingPartners()->firstWhere('shipping_partners.id', $this->input('shipping_partner_id'))
        ) {
            $this->errors()->add('shipping_partner_code', static::ERROR_NOT_EXIST);
            return;
        };

        $errors = [];
        $code   = trim($this->input['code']);
        if (
            $this->merchant->orders()->firstWhere('code', $code)
        ) {
            $errors['code'] = static::ERROR_ALREADY_EXIST;
        }

        $bankErrors = $this->validateBank();
        if (!empty($bankErrors)) {
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

        $extraServicesErrors = $this->validateExtraServices();
        if (!empty($extraServicesErrors)) {
            $this->errors()->add('extra_services_errors', $extraServicesErrors);
        }

        if (!empty($this->input['created_at_origin'])) {
            $createdAtOrigin = Service::order()->formatDateTime($this->input['created_at_origin']);
            if ($createdAtOrigin->gt(date('Y-m-d 23:59:59'))) {
                $this->errors()->add('created_at_origin', static::ERROR_INVALID);
            }
        }

        $shippingAmount = isset($this->input['shipping_amount']) ? floatval($this->input['shipping_amount']) : 0;
        $discountAmount = isset($this->input['discount_amount']) ? floatval($this->input['discount_amount']) : 0;
        $deliveryFee    = isset($this->input['delivery_fee']) ? floatval($this->input['delivery_fee']) : 0;
        $this->totalAmount = $this->totalAmount + $shippingAmount + $deliveryFee - $discountAmount;

        if (isset($this->input['payment_amount']) && floatval($this->input['payment_amount']) > floatval($this->totalAmount)) {
            $errors['payment_amount'] = self::ERROR_GREATER;
        }
        if (!empty($errors)) {
            $this->errors()->add('errors', $errors);
        }

        if (!empty($errors) || !empty($bankErrors) || !empty($locationErrors) || !empty($skuErrors) || !empty($extraServicesErrors)) {
            return;
        }
    }

    /**
     * @return array
     */
    protected function validateExtraServices()
    {
        $extraServicesErrors = [];
        $extraServicesAmount = 0;
        if (!empty($this->input['extra_services'])) {
            $extraServices = $this->input['extra_services'];
            $line          = 0;
            foreach ($extraServices as $extraService) {
                $line++;
                $lineKey = 'line_' . $line;
                if (empty($extraService['name'])) {
                    $extraServicesErrors[$lineKey]['name'] = static::ERROR_INVALID;
                }

                if (!isset($extraService['amount']) || !is_numeric($extraService['amount'])) {
                    $extraServicesErrors[$lineKey]['amount'] = static::ERROR_INVALID;
                }

                if (!empty($extraServicesErrors[$lineKey])) {
                    continue;
                }

                $extraServicesAmount   += floatval($extraService['amount']);
                $this->extraServices[] = [
                    'name' => trim($extraService['name']),
                    'amount' => floatval($extraService['amount'])
                ];
            }
        }

        $this->totalAmount = $this->totalAmount + $extraServicesAmount;

        return $extraServicesErrors;
    }

    /**
     * @return array
     */
    protected function validateBank()
    {
        $bankErrors = [];
        if ($this->input['payment_type'] == Order::PAYMENT_TYPE_ADVANCE_PAYMENT) {
            if (
                !empty($this->input['payment_method']) &&
                $this->input['payment_method'] == OrderTransaction::METHOD_BANK_TRANSFER &&
                (!empty($this->input['bank_name']) || !empty($this->input['bank_account']))
            ) {
                if (empty($this->input['bank_name'])) {
                    $bankErrors['bank_name'] = static::ERROR_REQUIRED;
                }

                if (empty($this->input['bank_account'])) {
                    $bankErrors['bank_account'] = static::ERROR_REQUIRED;
                }
            }

            if (
                !empty($this->input['payment_method']) ||
                !empty($this->input['payment_amount']) ||
                !empty($this->input['payment_time'])
            ) {
                if (empty($this->input['payment_method'])) {
                    $bankErrors['payment_method'] = static::ERROR_REQUIRED;
                }

                if (empty($this->input['payment_amount'])) {
                    $bankErrors['payment_amount'] = static::ERROR_REQUIRED;
                }

                if (empty($this->input['payment_time'])) {
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
        $orderSkus   = $this->input['orderSkus'];
        $SkuRequired = ['price', 'quantity'];
        $line        = 0;
        $skuErrors   = [];
        $skuIds      = [];

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

            foreach (['discount_amount', 'price'] as $key) {
                if (!isset($orderSku[$key]) || !is_numeric($orderSku[$key]) || floatval($orderSku[$key]) < 0) {
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
            if (!$sku instanceof Sku && !empty($orderSku['sku_code'])) {
                $skuId = trim($orderSku['sku_code']);
                $sku   = $this->mapExternalSkuCode($skuId);
            }
            if (!$sku instanceof Sku && !empty($orderSku['code'])) {
                $skuId = trim($orderSku['code']);
                $sku   = $this->tenant->skus()->firstWhere('code', $skuId);
            }

            if (!$sku) {
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

            if (in_array($skuId, $skuIds)) {
                $skuErrors[$lineKey][self::ERROR_ALREADY_EXIST][] = 'sku_id';
            }

            if (!empty($skuErrors[$lineKey])) {
                continue;
            }

            $price           = floatval($orderSku['price']);
            $quantity        = intval($orderSku['quantity']);
            $discount_amount = floatval($orderSku['discount_amount']);
            $tax             = isset($orderSku['tax']) ? floatval($orderSku['tax']) : null;


            $orderAmount = $price * $quantity;
            $totalAmount = ($orderAmount + ($orderAmount * floatval($tax) * 0.01)) - $discount_amount;

            $this->orderSkus[] = [
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

        return $skuErrors;
    }

    /**
     * @param $externalSkuCode
     * @return mixed|\Modules\Product\Models\Sku|null
     */
    protected function mapExternalSkuCode($externalSkuCode)
    {
        $marketplaceCode = Arr::get($this->input, 'marketplace_code');
        $store           = Store::query()->firstWhere([
            'tenant_id' => $this->tenant->id,
            'marketplace_code' => $marketplaceCode
        ]);
        if (!$store instanceof Store) {
            return null;
        }
        $storeSku = $store->storeSkus()->firstWhere('code', $externalSkuCode);
        return ($storeSku) ? $storeSku->sku : null;
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
