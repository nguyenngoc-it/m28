<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Gobiz\Support\Conversion;
use Modules\Order\Models\Order;
use Modules\Product\Models\ProductServicePrice;
use Modules\Service\Models\Service;

class SetOrderReturnServiceJob extends Job
{
    public $queue = 'service_pack';
    /** @var Order */
    protected $order;

    /**
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        $orderPackingServices = [];
        foreach ($this->order->orderSkus as $orderSku) {
            $sku = $orderSku->sku;
            if ($sku) {
                $productServicePrices = $sku->product->productServicePrices;
                if ($productServicePrices->count() > 0) {
                    /** @var ProductServicePrice $productServicePrice */
                    foreach ($productServicePrices as $productServicePrice) {
                        $service = $productServicePrice->service;

                        /**
                         * Set Dich vụ hoàn trên đơn
                         */
                        if ($service->type == Service::SERVICE_TYPE_IMPORTING_RETURN_GOODS) {
                            $orderPackingServices[$productServicePrice->service_price_id]['service_id'] = $productServicePrice->service_id;
                            $orderPackingServices[$productServicePrice->service_price_id]['price']      = $productServicePrice->servicePrice->price;
                        }
                    }
                }
            }
        }

        $this->order->importReturnGoodsServicePrices()->sync($orderPackingServices);
        $this->order->service_import_return_goods_amount = Conversion::convertMoney($this->order->orderImportReturnGoodsServices()->sum('price') * $this->order->orderSkus()->sum('quantity'));
        $this->order->save();
    }
}
