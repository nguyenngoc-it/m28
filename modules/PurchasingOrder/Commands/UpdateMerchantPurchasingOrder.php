<?php

namespace Modules\PurchasingOrder\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductEvent;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\Service\Models\Service;
use Modules\User\Models\User;

class UpdateMerchantPurchasingOrder
{
    /** @var PurchasingOrder */
    protected $purchasingOrder;
    protected $input;
    /** @var User */
    protected $user;
    protected $payloadLogs = [];

    public function __construct(PurchasingOrder $purchasingOrder, array $input, User $user)
    {
        $this->purchasingOrder = $purchasingOrder;
        $this->input           = $input;
        $this->user            = $user;
    }

    /**
     * @return Product
     */
    public function handle()
    {
        return DB::transaction(function () {
            $this->updateBase();
            $this->updateServices();
            if ($this->payloadLogs) {
                $this->purchasingOrder->logActivity(ProductEvent::UPDATE, $this->user, $this->payloadLogs);
            }
            return $this->purchasingOrder->refresh();
        });

    }

    protected function updateBase()
    {
        $isPutaway   = Arr::get($this->input, 'is_putaway');
        $warehouseId = Arr::get($this->input, 'warehouse_id');
        foreach ([
                     'is_putaway' => $isPutaway,
                     'warehouse_id' => $warehouseId
                 ] as $field => $value) {
            if (!is_null($value)) {
                $this->payloadLogs[$field]['old'] = $this->purchasingOrder->{$field};
                $this->payloadLogs[$field]['new'] = $value;
                $this->purchasingOrder->{$field}  = $value;
            }
        }
        $this->purchasingOrder->purchasingPackages()->update(['is_putaway' => true, 'destination_warehouse_id' => $this->purchasingOrder->warehouse_id]);
        $this->purchasingOrder->save();
    }

    protected function updateServices()
    {
        $services = Arr::get($this->input, 'services');
        if (!is_null($services)) {
            $syncServices = [];
            foreach ($services as $serviceId) {
                $service = Service::find($serviceId);
                if ($service instanceof Service) {
                    $servicePriceDefault                          = $service->servicePriceDefault();
                    $syncServices[$serviceId]['tenant_id']        = $this->purchasingOrder->tenant_id;
                    $syncServices[$serviceId]['service_price_id'] = $servicePriceDefault->id;
                }
            }
            if ($syncServices) {
                $this->purchasingOrder->services()->sync($syncServices);
                \Modules\Service::purchasingOrder()->syncServiceToPackage($this->purchasingOrder);
            }
        }
    }
}
