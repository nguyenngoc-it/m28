<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class ImportedForUpdateValidator extends Validator
{
    /**
     * @var ShippingPartner
     */
    protected $shippingPartner;
    /** @var Order */
    protected $order;
    /** @var Tenant */
    protected $tenant;
    /** @var array */
    protected $syncSkus;
    /** @var Location|null */
    protected $locationCountry, $locationProvince, $locationDistrict, $locationWard;

    public function __construct(User $user, array $row, Tenant $tenant = null)
    {
        parent::__construct($row, $user);
        $this->user   = $user;
        $this->tenant = $tenant;
        if (empty($tenant)) {
            $this->tenant = $this->user->tenant;
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'order_code' => 'required',
            'receiver_name' => 'required',
            'receiver_phone' => 'required',
            'receiver_country' => 'required',
            'receiver_province' => 'required',
            'receiver_district' => 'required',
            'receiver_ward' => '',
            'receiver_address' => 'required',
            'skus' => 'required|array',
            'cod' => 'numeric'
        ];
    }

    /**
     * @return Location|null
     */
    public function getLocationDistrict(): ?Location
    {
        return $this->locationDistrict;
    }

    /**
     * @return Location|null
     */
    public function getLocationWard(): ?Location
    {
        return $this->locationWard;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return array
     */
    public function getSyncSkus(): array
    {
        return $this->syncSkus;
    }

    /**
     * @return Location|null
     */
    public function getLocationCountry(): ?Location
    {
        return $this->locationCountry;
    }

    /**
     * @return Location|null
     */
    public function getLocationProvince(): ?Location
    {
        return $this->locationProvince;
    }

    /**
     * @return ShippingPartner|null
     */
    public function getShippingPartner(): ?ShippingPartner
    {
        return $this->shippingPartner;
    }

    protected function customValidate()
    {
        $orderCode             = $this->input('order_code');
        $receiverCountryLabel  = $this->input('receiver_country');
        $receiverProvinceLabel = $this->input('receiver_province');
        $receiverDistrictLabel = $this->input('receiver_district');
        $receiverWardLabel     = $this->input('receiver_ward');
        $skus                  = $this->input('skus');
        $shippingPartnerCode   = $this->input('shipping_partner_code');


        $this->order = Order::query()->where([
            'code' => $orderCode,
            'tenant_id' => $this->tenant->id,
        ])->first();

        if (!$this->order) {
            $this->errors()->add('order', static::ERROR_EXISTS);
            return;
        }
        $this->shippingPartner = ShippingPartner::query()
                                            ->where('code', $shippingPartnerCode)
                                            ->where('tenant_id', $this->tenant->id)
                                            ->first();
        if (!$this->shippingPartner){
            $this->errors()->add('shipping_partner', static::ERROR_EXISTS);
            return;
        }
        $shippingPartnerLocationIds = $this->shippingPartner->locations->pluck('id')->toArray();
        if(!in_array($this->order->merchant->location_id, $shippingPartnerLocationIds)) {
            $this->errors()->add('shipping_partner', static::ERROR_INVALID);
        }

        if (!in_array($this->order->merchant_id, $this->user->merchants->pluck('id')->all()) && $this->user->username != 'fobiz') {
            $this->errors()->add('order', 'not_to_access_order');
            return;
        }
        if ($this->order && !in_array($this->order->status, [Order::STATUS_WAITING_INSPECTION, Order::STATUS_WAITING_CONFIRM])) {
            $this->errors()->add('order', static::ERROR_STATUS_INVALID);
            return;
        }

        /**
         * Validate receiver locations
         */
        $this->locationCountry = Location::query()->where([
            'type' => Location::TYPE_COUNTRY,
            'active' => true
        ])->where(function (Builder $builder) use ($receiverCountryLabel) {
            $builder->where('label', $receiverCountryLabel)
                ->orWhere('code', $receiverCountryLabel);
        })->first();
        if (!$this->locationCountry) {
            $this->errors()->add('order', 'receiver_country_invalid');
            return;
        }

        $this->locationProvince = $this->locationCountry->childrens->filter(function (Location $location) use ($receiverProvinceLabel) {
            return (($location->label == $receiverProvinceLabel) || ($location->code == $receiverProvinceLabel));
        })->first();
        if ($receiverProvinceLabel && !$this->locationProvince) {
            $this->errors()->add('order', 'receiver_province_invalid');
            return;
        }

        $this->locationDistrict = $this->locationProvince->childrens->filter(function (Location $location) use ($receiverDistrictLabel) {
            return (($location->label == $receiverDistrictLabel) || ($location->code == $receiverDistrictLabel));
        })->first();
        if ($receiverDistrictLabel && !$this->locationDistrict) {
            $this->errors()->add('order', 'receiver_district_invalid');
            return;
        }
        if ($receiverWardLabel) {
            $this->locationWard = $this->locationDistrict->childrens->filter(function (Location $location) use ($receiverWardLabel) {
                return (($location->label == $receiverWardLabel) || ($location->code == $receiverWardLabel));
            })->first();
            if (!$this->locationWard) {
                $this->errors()->add('order', 'receiver_ward_invalid');
                return;
            }
        }

        /**
         * Validate skus
         */
        $skuCodes = collect($skus)->pluck('sku_code')->all();
        // $dbSkus   = Sku::query()
        //     ->select(['skus.*'])
        //     ->where('skus.tenant_id', $this->tenant->id)
        //     ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
        //     ->where(function($query) {
        //         return $query->where('skus.merchant_id', $this->order->merchant_id)
        //             ->orWhere('product_merchants.merchant_id', $this->order->merchant_id);
        //     })
        //     ->whereIn('skus.code', $skuCodes)->count();

        $dbSkus = Sku::select('skus.*')
                    ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                    ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                    ->where('skus.tenant_id', $this->tenant->id)
                    ->where(function($query) use ($skuCodes){
                        return $query->whereIn('store_skus.code', $skuCodes)
                                        ->orWhereIn('skus.code', $skuCodes);
                    })
                    ->where(function($query) {
                        return $query->where('skus.merchant_id', $this->order->merchant_id)
                                        ->orWhere('product_merchants.merchant_id', $this->order->merchant_id);
                    })
                    ->groupBy('skus.id')
                    ->get();

        if (count($skuCodes) != count($dbSkus)) {
            $this->errors()->add('skus', self::ERROR_INVALID);
            return;
        }
    }
}
