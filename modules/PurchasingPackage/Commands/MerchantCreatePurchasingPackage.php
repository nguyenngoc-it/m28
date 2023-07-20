<?php

namespace Modules\PurchasingPackage\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;
use Modules\PurchasingPackage\Models\PurchasingPackageService;
use Modules\PurchasingPackage\Services\PurchasingPackageEvent;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;

class MerchantCreatePurchasingPackage
{
    /**
     * @var array
     */
    protected $input;

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var array
     */
    protected $packageItems;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Warehouse
     */
    protected $destinationWarehouse;

    /** @var ShippingPartner|null $shippingPartner */
    protected $shippingPartner;
    protected $serviceIds;

    /**
     * ImportOrder constructor.
     * @param array $input
     * @param Warehouse $destinationWarehouse
     * @param array $packageItems
     * @param ShippingPartner $shippingPartner
     * @param User $user
     */
    public function __construct(array $input, Warehouse $destinationWarehouse, array $packageItems, User $user, ShippingPartner $shippingPartner = null)
    {
        $this->packageItems         = $packageItems;
        $this->shippingPartner      = $shippingPartner;
        $this->destinationWarehouse = $destinationWarehouse;
        $this->input                = $input;
        $this->user                 = $user;
        $this->serviceIds           = Arr::get($input, 'service_ids', []);

    }

    /**
     * @return PurchasingPackage
     */
    public function handle()
    {
        $purchasingPackage = $this->makePurchasingPackage();
        $this->makePackageItems($purchasingPackage);
        $this->makePackageServices($purchasingPackage);

        $purchasingPackage->logActivity(PurchasingPackageEvent::CREATE, $this->user);

        return $purchasingPackage;
    }

    /**
     * @return PurchasingPackage
     */
    protected function makePurchasingPackage()
    {
        $merchantCode = data_get($this->input, 'merchant_code');
        if ($merchantCode) {
            $merchant = Merchant::query()->where(['code' => $merchantCode])->where('tenant_id', $this->user->tenant->id)->first();
        } else {
            $merchant = $this->user->merchant;
        }
        $data = array_merge($this->input, [
            'tenant_id' => $this->user->tenant_id,
            'code' => Str::random(8),
            'creator_id' => $this->user->id,
            'merchant_id' => $merchant->id,
            'is_putaway' => true,
            'status' => PurchasingPackage::STATUS_INIT,
            'destination_warehouse_id' => isset($this->destinationWarehouse->id) ? $this->destinationWarehouse->id : 0,
            'shipping_partner_id' => isset($this->shippingPartner->id) ? $this->shippingPartner->id : 0,
            'service_amount' => 0,
        ]);

        $purchasingPackage       = PurchasingPackage::create($data);
        $code                    = strtoupper(substr($merchant->code, 0, 2) . str_pad($purchasingPackage->id, 12, '0', STR_PAD_LEFT));
        $purchasingPackage->code = $code;
        $purchasingPackage->save();
        return $purchasingPackage;
    }

    /**
     * @param PurchasingPackage $purchasingPackage
     */
    protected function makePackageItems(PurchasingPackage $purchasingPackage)
    {
        $quantity = 0;
        foreach ($this->packageItems as $packageItem) {
            if (!isset($packageItem['purchasing_variant_id'])) {
                $packageItem['purchasing_variant_id'] = null;
            }
            PurchasingPackageItem::create(array_merge($packageItem, [
                'purchasing_package_id' => $purchasingPackage->id,
            ]));
            if (!empty($packageItem['quantity'])) {
                $quantity += $packageItem['quantity'];
            }
        }

        $purchasingPackage->quantity = $quantity;
        $purchasingPackage->save();
    }

    /**
     * Tự động lấy tất cả các dịch vụ trên sản phẩm có trong kiện nhập nếu có chọn dịch vụ
     *
     * @param PurchasingPackage $purchasingPackage
     */
    protected function makePackageServices(PurchasingPackage $purchasingPackage)
    {
        $purchasingPackageServices = [];
        $checkServiceIds           = $this->serviceIds;
        $skus                      = [];
        foreach ($purchasingPackage->purchasingPackageItems as $purchasingPackageItem) {
            if ($sku = $purchasingPackageItem->sku) {
                if ($sku->product->servicePrices) {
                    /** @var ServicePrice $servicePrice */
                    foreach ($sku->product->servicePrices as $servicePrice) {
                        if (in_array($servicePrice->service->id, $this->serviceIds)) {
                            if (($key = array_search($servicePrice->service->id, $checkServiceIds)) !== false) {
                                unset($checkServiceIds[$key]);
                            }
                            $purchasingPackageServices[$servicePrice->service->id][$servicePrice->id]['price']    = $servicePrice->price;
                            $purchasingPackageServices[$servicePrice->service->id][$servicePrice->id]['quantity'] = empty($purchasingPackageServices[$servicePrice->service->id][$servicePrice->id]['quantity']) ? $purchasingPackageItem->quantity :
                                $purchasingPackageServices[$servicePrice->service->id][$servicePrice->id]['quantity'] + $purchasingPackageItem->quantity;
                            $purchasingPackageServices[$servicePrice->service->id][$servicePrice->id]['amount']   = empty($purchasingPackageServices[$servicePrice->service->id][$servicePrice->id]['amount']) ? ($servicePrice->price * $purchasingPackageItem->quantity) :
                                $purchasingPackageServices[$servicePrice->service->id][$servicePrice->id]['amount'] + $servicePrice->price * $purchasingPackageItem->quantity;
                            $purchasingPackageServices[$servicePrice->service->id][$servicePrice->id]['skus'][]   = [
                                'sku_id' => $sku->id,
                                'sku_code' => $sku->code,
                                'sku_name' => $sku->name,
                                'quantity' => $purchasingPackageItem->quantity
                            ];
                        }
                    }
                }
                $skus[] = [
                    'sku_id' => $sku->id,
                    'sku_code' => $sku->code,
                    'sku_name' => $sku->name,
                    'quantity' => $purchasingPackageItem->quantity
                ];
            }
        }
        /**
         * Nếu không có sản phẩm nào chứa dịch vụ đc chọn thì áp dụng tất cả sản phẩm theo mức giá mặc định của dịch vụ
         */
        if ($checkServiceIds) {
            foreach ($checkServiceIds as $checkServiceId) {
                /** @var Service $service */
                $service                                                                = Service::find($checkServiceId);
                $servicePrice                                                           = $service->servicePriceDefault();
                $quantity                                                               = $purchasingPackage->purchasingPackageItems->sum('quantity');
                $purchasingPackageServices[$service->id][$servicePrice->id]['price']    = $servicePrice->price;
                $purchasingPackageServices[$service->id][$servicePrice->id]['quantity'] = $quantity;
                $purchasingPackageServices[$service->id][$servicePrice->id]['amount']   = $servicePrice->price * $quantity;
                $purchasingPackageServices[$service->id][$servicePrice->id]['skus']     = $skus;
            }
        }

        /**
         * Create $purchasingPackageServices
         */
        foreach ($purchasingPackageServices as $serviceId => $purchasingPackageServicePrices) {
            foreach ($purchasingPackageServicePrices as $servicePriceId => $data) {
                PurchasingPackageService::updateOrCreate(
                    [
                        'service_price_id' => $servicePriceId,
                        'purchasing_package_id' => $purchasingPackage->id,
                    ],
                    [
                        'service_id' => $serviceId,
                        'price' => $data['price'],
                        'quantity' => $data['quantity'],
                        'amount' => $data['amount'],
                        'skus' => $data['skus'],
                    ]
                );
            }
        }
    }

}
