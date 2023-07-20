<?php

namespace Modules\Service\Listeners;

use App\Base\QueueableListener;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Jobs\SetOrderReturnServiceForSellerJob;
use Modules\OrderPacking\Jobs\SetOrderPackingServiceForSellerJob;
use Modules\Product\Jobs\SetServicePackForSellerProductJob;
use Modules\Service\Events\ServicePackSellerAdded;
use Modules\Service\Services\ServiceEvent;

class ServicePackSellerAddedListener extends QueueableListener
{
    /**
     * @param ServicePackSellerAdded $event
     */
    public function handle(ServicePackSellerAdded $event)
    {
        $servicePack = $event->servicePack->refresh();
        $sellerIds   = $event->sellerIds;
        $sellers     = Merchant::query()->whereIn('id', $sellerIds)->get();
        $servicePack->logActivity(ServiceEvent::SERVICE_PACK_ADD_SELLER, $event->creator, [
            'sellers' => $sellers->map(function (Merchant $merchant) {
                return $merchant->only(['id', 'code', 'name', 'username']);
            })
        ]);

        /**
         * Cập nhật tất cả dịch vụ sản phẩm của seller theo gói dịch vụ
         */
        /** @var Merchant $merchant */
        foreach ($sellers as $merchant) {
            dispatch(new SetServicePackForSellerProductJob($merchant->id, $event->creator));
        }

        /**
         * Cập nhật tất cả dịch vụ xuất của YCĐH đối với đơn chưa xác nhận đóng hàng (Chờ đóng gói, chờ nhặt hàng và chờ xử lý)
         */
        foreach ($sellers as $merchant) {
            dispatch(new SetOrderPackingServiceForSellerJob($merchant->id));
        }

        /**
         * Cập nhật tất cả các dịch vụ hoàn của đơn chưa xác nhận hoàn (khác đã hoàn)
         */
        foreach ($sellers as $merchant) {
            dispatch(new SetOrderReturnServiceForSellerJob($merchant->id));
        }
    }
}
