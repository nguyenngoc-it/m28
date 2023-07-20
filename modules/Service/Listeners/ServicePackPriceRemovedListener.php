<?php

namespace Modules\Service\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Jobs\SetOrderReturnServiceForSellerJob;
use Modules\OrderPacking\Jobs\SetOrderPackingServiceForSellerJob;
use Modules\Product\Jobs\SetServicePackForSellerProductJob;
use Modules\Service\Events\ServicePackPriceRemoved;
use Modules\Service\Models\ServicePrice;
use Modules\Service\Services\ServiceEvent;

class ServicePackPriceRemovedListener extends QueueableListener
{
    /**
     * @param ServicePackPriceRemoved $event
     */
    public function handle(ServicePackPriceRemoved $event)
    {
        $servicePack     = $event->servicePack->refresh();
        $servicePriceIds = $event->servicePriceIds;
        $servicePrices   = ServicePrice::query()->whereIn('id', $servicePriceIds)->with('serviceRelate')->get();
        $servicePack->logActivity(ServiceEvent::SERVICE_PACK_REMOVE_PRICE, $event->creator, [
            'service_prices' => $servicePrices->map(function (ServicePrice $servicePrice) {
                return [
                    'service_price' => $servicePrice->only(['id', 'price', 'yield_price', 'deduct']),
                    'service' => $servicePrice->service->only(['id', 'type', 'code', 'name'])
                ];
            })
        ]);

        /**
         * Cập nhật dịch vụ sản phẩm của tất cả các seller đang sử dụng gói
         */
        foreach ($servicePack->merchants as $merchant) {
            dispatch(new SetServicePackForSellerProductJob($merchant->id, $event->creator));
        }

        /**
         * Cập nhật tất cả dịch vụ xuất của YCĐH đối với đơn chưa xác nhận đóng hàng (Chờ đóng gói, chờ nhặt hàng và chờ xử lý)
         */
        foreach ($servicePack->merchants as $merchant) {
            dispatch(new SetOrderPackingServiceForSellerJob($merchant->id));
        }

        /**
         * Cập nhật tất cả các dịch vụ hoàn của đơn chưa xác nhận hoàn (khác đã hoàn)
         */
        foreach ($servicePack->merchants as $merchant) {
            dispatch(new SetOrderReturnServiceForSellerJob($merchant->id));
        }
    }
}
