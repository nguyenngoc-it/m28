<?php /** @noinspection ALL */

namespace Modules\Order\Validators;

use App\Base\Validator;
use Gobiz\Support\Helper;
use Modules\Location\Models\Location;
use Modules\Location\Models\LocationSearch;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Models\Sale;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderTransaction;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class MerchantImportedOrderValidator extends Validator
{
    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var Tenant
     */
    protected $tenant;


    /**
     * @var User
     */
    protected $creator;

    /**
     * @var Sale
     */
    protected $sale;

    /**
     * @var Sku
     */
    protected $sku;

    /**
     * @var SkuCombo
     */
    protected $skuCombo;

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
     * @var array
     */
    protected $insertedOrderKeys = [];

    protected $dropship = false;

    /**
     * MerchantImportedOrderValidator constructor.
     * @param User $creator
     * @param array $input
     * @param array $insertedOrderKeys
     * @param boolean $dropship
     */
    public function __construct(User $creator, array $input, $insertedOrderKeys = [], $dropship = false)
    {
        $this->creator           = $creator;
        $this->tenant            = $creator->tenant;
        $this->merchant          = $creator->merchant;
        $this->insertedOrderKeys = $insertedOrderKeys;
        $this->dropship          = $dropship;

        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'shipping_partner_code' => 'required_with:freight_bill',
            'sku_code' => 'required',
            'quantity' => 'required|numeric|gt:0',
            'price' => 'numeric|gte:0',
            'total_amount' => 'numeric|gte:0|nullable',
            'payment_amount' => 'numeric|gte:0|nullable',
            'payment_time' => 'date_format:Y/m/d|nullable'

        ];
    }

    protected function customValidate()
    {
        $country = $this->merchant->getCountry();
        if (!$country instanceof Location) {
            $this->errors()->add('country', static::ERROR_NOT_EXIST);
            return;
        }
        $nameStore = $this->input('name_store');
        $code      = $this->input('code');
        $sku_code  = trim($this->input('sku_code'));
        if (empty($this->insertedOrderKeys) && empty($code)) {
            $this->errors()->add('order_code', static::ERROR_REQUIRED);
        }

        $validateCode = preg_match("/\s/s", trim($code));
        if ($validateCode) {
            $this->errors()->add('order_code', static::ERROR_INVALID);
            return;
        }
        /** @var Product $product */
        $pro = null;
        $sku = Sku::select('skus.*')
                    ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                    ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                    ->where(function($query) use ($sku_code){
                        return $query->where('store_skus.code', $sku_code)
                                        ->orWhere('skus.code', $sku_code);
                    })
                    ->where(function($query) {
                        return $query->where('skus.merchant_id', $this->merchant->id)
                                        ->orWhere('product_merchants.merchant_id', $this->merchant->id);
                    })
                    ->first();

        if ($sku) {
            $this->sku = $sku;
            $pro = $sku->product;
        } else {
            // Check code có phải Sku Combo không
            $skuCombo = SkuCombo::where('merchant_id' , $this->merchant->id)
                                ->where('code', $sku_code)
                                ->first();

            if ($skuCombo) {
                $this->skuCombo = $skuCombo;
            }
        }

        // foreach ($this->merchant->products as $product) {
        //     /** @var Sku $sku */
        //     foreach ($product->skus as $sku) {
        //         if (trim($sku->code) == $sku_code) {
        //             $this->sku = $sku;
        //             $pro       = $product;
        //             break;
        //         }
        //     }
        //     if ($this->sku) {
        //         break;
        //     }
        // }
        // if ($nameStore) {
        //     $stores = $this->merchant->stores;
        //     if ($stores) {
        //         $datas = [];
        //         foreach ($stores as $store) {
        //             $datas[] = $store->getNameStore();
        //         }
        //         if (!in_array($nameStore, $datas)) {
        //             $this->errors()->add('name_store', static::ERROR_INVALID);
        //             return;
        //         }
        //     }
        // }

        if ($nameStore) {
            $store = Store::where('merchant_id', $this->merchant->id)
                            ->where('name', $nameStore)
                            ->first();
            if (!$store) {
                $this->errors()->add('name_store', static::ERROR_INVALID);
                return;
            }
        }

        if (!($this->sku || $this->skuCombo)) {
            $this->errors()->add('sku_code', static::ERROR_NOT_EXIST);
            return;
        }

        if ($pro && $this->dropship != $pro->dropship) {
            $this->errors()->add('warning_dropship', $this->sku->code);
            return;
        }

        if (!empty($code)) {
            if (!$this->merchant->status) {
                $this->errors()->add('merchant_code', static::ERROR_NOT_EXIST);
                return;
            }

            if ($this->merchant->orders()->firstWhere('code', $code)) {
                $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            }

            if (empty($this->input['payment_type'])) {
                $this->errors()->add('payment_type', static::ERROR_REQUIRED);
            } else if (!in_array($this->input['payment_type'], Order::$paymentTypes)) {
                $this->errors()->add('payment_type', static::ERROR_INVALID);
            }

            $requireds = [
                'receiver_name',
                'receiver_phone',
                'receiver_address'
            ];

            foreach ($requireds as $required) {
                if (empty($this->input[$required])) {
                    $this->errors()->add($required, static::ERROR_REQUIRED);
                }
            }
        }

        $locationErrors = $this->validateLocation($country);
        if (!empty($locationErrors)) {
            foreach ($locationErrors as $key => $error) {
                $this->errors()->add($key, $error);
            }
        }

        if (!empty($this->input['shipping_partner_code'])) {
            if (
                !$this->shippingPartner = $country->getShippingPartnerByAliasOrCode($this->tenant->id, strtolower($this->input['shipping_partner_code']))
            ) {
                $this->errors()->add('shipping_partner_code', static::ERROR_NOT_EXIST);
                return;
            };
        }

        $freightBill = $this->input('freight_bill');
        if (
            $freightBill &&
            $this->shippingPartner instanceof ShippingPartner
        ) {
            $orderExist = Order::query()->where(['freight_bill' => $freightBill, 'shipping_partner_id' => $this->shippingPartner->id])->first();
            if ($orderExist) {
                $this->errors()->add('freight_bill', static::ERROR_ALREADY_EXIST);
                return;
            }
        }

        $bankErrors = $this->validateBank();
        if (!empty($bankErrors)) {
            foreach ($bankErrors as $key => $error) {
                $this->errors()->add($key, $error);
            }
        }
    }

    /**
     * @return array
     */
    protected function validateBank()
    {
        $bankErrors = [];
        if ($this->input['payment_type'] == Order::PAYMENT_TYPE_ADVANCE_PAYMENT) {
            if (empty($this->input['payment_method'])) {
                $bankErrors['payment_method'] = static::ERROR_REQUIRED;
            }
            if (!empty($this->input['payment_method']) &&
                $this->input['payment_method'] == OrderTransaction::METHOD_CASH) {
                if (empty($this->input['payment_time'])) {
                    $bankErrors['payment_time'] = static::ERROR_REQUIRED;
                }
//                if(!empty($this->input['payment_time'])) {
//                    $paymentTime = Service::order()->formatDateTime($this->input['payment_time']);
//                    if($paymentTime->gt(date('Y-m-d 23:59:59'))) {
//                        $this->errors()->add('payment_time', static::ERROR_INVALID);
//                    }
//                }
                if (empty($this->input['payment_amount'])) {
                    $bankErrors['payment_amount'] = static::ERROR_REQUIRED;
                }
            }
            if (!empty($this->input['payment_method']) &&
                $this->input['payment_method'] == OrderTransaction::METHOD_BANK_TRANSFER) {
                if (empty($this->input['bank_name'])) {
                    $bankErrors['bank_name'] = static::ERROR_REQUIRED;
                }
                if (empty($this->input['bank_account'])) {
                    $bankErrors['bank_account'] = static::ERROR_REQUIRED;
                }
                if (empty($this->input['standard_code'])) {
                    $bankErrors['standard_code'] = static::ERROR_REQUIRED;
                }
                if (empty($this->input['payment_time'])) {
                    $bankErrors['payment_time'] = static::ERROR_REQUIRED;
                }
//                if(!empty($this->input['payment_time'])) {
//                    $paymentTime = Service::order()->formatDateTime($this->input['payment_time']);
//                    if($paymentTime->gt(date('Y-m-d 23:59:59'))) {
//                        $this->errors()->add('payment_time', static::ERROR_INVALID);
//                    }
//                }
                if (empty($this->input['payment_amount'])) {
                    $bankErrors['payment_amount'] = static::ERROR_REQUIRED;
                }
            }
        } else { // trả sau thì k cho nhập
            foreach (['payment_method', 'bank_name', 'bank_account', 'payment_amount', 'payment_time', 'standard_code', 'payment_note'] as $key) {
                if (!empty($this->input[$key])) {
                    $bankErrors[$key] = 'empty';
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
        $countryCode    = $country->code;
        $locationErrors = [];
        if (!empty($this->input['province'])) {
            $keywordProvince = $countryCode == Location::COUNTRY_VIETNAM ?
                Helper::convert_vi_to_en(strtolower($this->input['province'])) : Helper::clean(strtolower($this->input['province']));
            $keywordProvince = str_replace(' ', '', $keywordProvince);
            /** @var LocationSearch|null $locationProvinceSearch */
            $locationProvinceSearch = LocationSearch::query()->where([
                'type' => Location::TYPE_PROVINCE,
                'keyword' => $keywordProvince,
                'parent_code' => $countryCode
            ])->first();
            if (!$locationProvinceSearch) {
                $locationErrors['province'] = static::ERROR_NOT_EXIST;
                return $locationErrors;
            }
            $this->receiverProvince = $locationProvinceSearch->location;

            if (!empty($this->input['district'])) {
                $keywordDistrict = $countryCode == Location::COUNTRY_VIETNAM ?
                    Helper::convert_vi_to_en(strtolower($this->input['district'])) : Helper::clean(strtolower($this->input['district']));
                $keywordDistrict = str_replace(' ', '', $keywordDistrict);
                /** @var LocationSearch|null $locationDistrictSearch */
                $locationDistrictSearch = LocationSearch::query()->where([
                    'type' => Location::TYPE_DISTRICT,
                    'keyword' => $keywordDistrict,
                    'parent_code' => $this->receiverProvince->code
                ])->first();
                if (!$locationDistrictSearch) {
                    $locationErrors['district'] = static::ERROR_NOT_EXIST;
                    return $locationErrors;
                }
                $this->receiverDistrict = $locationDistrictSearch->location;

                if (!empty($this->input['ward'])) {
                    $keywordWard = Helper::convert_vi_to_en(strtolower($this->input['district'])) ?
                        Helper::convert_vi_to_en(strtolower($this->input['ward'])) : Helper::clean(strtolower($this->input['ward']));
                    $keywordWard = str_replace(' ', '', $keywordWard);
                    /** @var LocationSearch|null $locationWardSearch */
                    $locationWardSearch = LocationSearch::query()->where([
                        'type' => Location::TYPE_WARD,
                        'keyword' => $keywordWard,
                        'parent_code' => $this->receiverDistrict->code
                    ])->first();
                    if (!$locationWardSearch) {
                        $locationErrors['ward'] = static::ERROR_NOT_EXIST;
                        return $locationErrors;
                    }
                    $this->receiverWard = $locationWardSearch->location;
                }
            }

        }

        return $locationErrors;
    }

    /**
     * @return Sku
     */
    public function getSku()
    {
        return $this->sku;
    }


    /**
     * @return Sku
     */
    public function getSkuCombo()
    {
        return $this->skuCombo;
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
