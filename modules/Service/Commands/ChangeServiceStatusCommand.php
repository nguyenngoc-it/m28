<?php
namespace Modules\Service\Commands;

use Modules\Product\Models\ProductServicePrice;
use Modules\Product\Services\ProductEvent;
use Modules\Service\Models\Service;
use Modules\Service\Services\ServiceEvent;
use Modules\User\Models\User;

class ChangeServiceStatusCommand
{
    /**
     * @var Service
     */
    protected $service;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var boolean
     */
    protected $confirm;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * ChangeStatusServiceCommand constructor.
     * @param Service $service
     * @param $status
     * @param $confirm
     * @param User $creator
     */
    public function __construct(Service $service, $status, $confirm, User $creator)
    {
        $this->service  = $service;
        $this->status   = $status;
        $this->confirm  = $confirm;
        $this->creator  = $creator;
    }

    /**
     * @return Service
     */
    public function handle()
    {
        if($this->service->status == $this->status) {
            return $this->service;
        }

        $logData = [
            'from' => $this->service->status,
            'to' => $this->status
        ];

        $this->service->status = $this->status;
        $this->service->save();

        $this->service->logActivity(ServiceEvent::CHANGE_STATUS, $this->creator, $logData);

        if(
            $this->status == Service::STATUS_INACTIVE &&
            $this->confirm
        ) { //nếu dừng dịch vụ và xác nhận xóa dịch vụ trên sản phẩm
           $productServicePrices = ProductServicePrice::query()->where('service_id', $this->service->id)->with(['product'])->get();

           /** @var ProductServicePrice $productServicePrice */
            foreach ($productServicePrices as $productServicePrice) {
               $productServicePrice->product->logActivity(ProductEvent::REMOVE_SERVICE, $this->creator, [
                   'service' => $this->service->only(['id', 'name', 'code'])
               ]);
           }

            ProductServicePrice::query()->where('service_id', $this->service->id)->delete();
        }

        return $this->service;
    }
}
