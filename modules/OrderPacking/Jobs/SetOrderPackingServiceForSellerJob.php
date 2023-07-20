<?php

namespace Modules\OrderPacking\Jobs;

use App\Base\Job;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\Product\Jobs\SetServicePackForProductJob;
use Modules\Service;

class SetOrderPackingServiceForSellerJob extends Job
{
    public $queue = 'service_pack';

    protected $merchantId;

    /**
     * @param $merchantId
     */
    public function __construct($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    public function handle()
    {
        $merchant      = Merchant::find($this->merchantId);
        $servicePack   = $merchant->servicePack;
        $validOrders   = $merchant->orders()->with(['orderSkus'])
            ->whereIn('status', [Order::STATUS_WAITING_PROCESSING, Order::STATUS_WAITING_PICKING, Order::STATUS_WAITING_PACKING])
            ->get();
        $orderProducts = [];
        /** @var Order $validOrder */
        foreach ($validOrders as $validOrder) {
            /**
             * Phải set lại dịch vụ của các sản phẩm trên đơn trước khi set dịch vụ xuất trên YCĐH
             */
            foreach ($validOrder->orderSkus as $orderSku) {
                if ($orderSku->sku) {
                    $orderProducts[$orderSku->sku->product_id] = $orderSku->sku->product;
                }
            }
        }
        foreach ($orderProducts as $product) {
            dispatch(new SetServicePackForProductJob($product, $servicePack, Service::user()->getSystemUserDefault()));
        }

        foreach ($validOrders as $validOrder) {
            if ($validOrder->orderPacking) {
                dispatch(new SetOrderPackingServiceJob($validOrder->orderPacking));
            }
        }
    }
}
