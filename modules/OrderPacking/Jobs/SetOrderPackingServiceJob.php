<?php

namespace Modules\OrderPacking\Jobs;

use App\Base\Job;
use Gobiz\Support\Conversion;
use Modules\Order\Models\OrderSku;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Product\Models\ProductServicePrice;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;

class SetOrderPackingServiceJob extends Job
{
    public $queue = 'service_pack';
    /** @var OrderPacking */
    protected $orderPacking;

    /**
     * @param OrderPacking $orderPacking
     */
    public function __construct(OrderPacking $orderPacking)
    {
        $this->orderPacking = $orderPacking;
    }

    public function handle()
    {
        $order = $this->orderPacking->order;
        /**
         * Khởi tạo dịch vụ cho OrderPacking
         */
        if ($order->dropship) {
            $order->orderPacking()->update([
                'service_amount' => $order->service_amount
            ]);
        } else {
            $orderPackingServices = $exportServicePriceIntoOrders = [];
            /** @var OrderSku $orderSku */
            foreach ($order->orderSkus as $orderSku) {
                $sku = $orderSku->sku;
                if ($sku) {
                    $productServicePrices = $sku->product->productServicePrices;
                    if ($productServicePrices->count() > 0) {
                        /** @var ProductServicePrice $productServicePrice */
                        foreach ($productServicePrices as $productServicePrice) {
                            if ($productServicePrice->service->type == Service::SERVICE_TYPE_EXPORT) {
                                $exportServicePriceIntoOrders[$productServicePrice->servicePrice->id] = [
                                    'service' => $productServicePrice->service,
                                    'service_price' => $productServicePrice->servicePrice
                                ];
                            }
                        }
                    }
                }
            }
            $exportServicePriceIntoOrders = array_values($exportServicePriceIntoOrders);
            foreach ($exportServicePriceIntoOrders as $exportServicePriceIntoOrder) {
                $service = $exportServicePriceIntoOrder['service'];
                /** @var ServicePrice $servicePrice */
                $servicePrice                                          = $exportServicePriceIntoOrder['service_price'];
                $orderPackingServices[$servicePrice->id]['service_id'] = $service->id;
                $orderPackingServices[$servicePrice->id]['order_id']   = $order->id;
                $orderPackingServices[$servicePrice->id]['price']      = $servicePrice->price;
                $orderPackingServices[$servicePrice->id]['quantity']   = $this->orderPacking->total_quantity;
                $orderPackingServices[$servicePrice->id]['amount']     = Conversion::convertMoney((($orderPackingServices[$servicePrice->id]['quantity'] - 1) * $servicePrice->yield_price + $servicePrice->price));
            }

            $this->orderPacking->servicePrices()->sync($orderPackingServices);
            $this->orderPacking->service_amount = round($this->orderPacking->orderPackingServices()->sum('amount'), 2);
            $this->orderPacking->save();
        }
    }
}
