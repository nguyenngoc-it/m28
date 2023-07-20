<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\Product\Jobs\SetServicePackForProductJob;
use Modules\Service;

class SetOrderReturnServiceForSellerJob extends Job
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
            ->where('status', '<>', Order::STATUS_RETURN_COMPLETED)
            ->get();
        $orderProducts = [];
        /** @var Order $validOrder */
        foreach ($validOrders as $validOrder) {
            /**
             * Phải set lại dịch vụ của các sản phẩm trên đơn trước khi set dịch vụ hoàn trên đơn
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
            dispatch(new SetOrderReturnServiceJob($validOrder));
        }
    }
}
