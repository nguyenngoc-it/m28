<?php

namespace Modules\PurchasingPackage\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;
use Modules\PurchasingPackage\Services\PurchasingPackageEvent;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class CreatePurchasingPackage
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

        $purchasingPackage->logActivity(PurchasingPackageEvent::CREATE, $this->user);

        return $purchasingPackage;
    }

    /**
     * @return PurchasingPackage
     */
    protected function makePurchasingPackage()
    {
        $data = array_merge($this->input, [
            'tenant_id' => $this->user->tenant_id,
            'code' => Str::random(8),
            'creator_id' => $this->user->id,
            'merchant_id' => 0,
            'is_putaway' => true,
            'status' => PurchasingPackage::STATUS_INIT,
            'destination_warehouse_id' => isset($this->destinationWarehouse->id) ? $this->destinationWarehouse->id : 0,
            'shipping_partner_id' => isset($this->shippingPartner->id) ? $this->shippingPartner->id : 0,
            'service_amount' => 0,
        ]);

        $purchasingPackage = PurchasingPackage::create($data);
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
}
