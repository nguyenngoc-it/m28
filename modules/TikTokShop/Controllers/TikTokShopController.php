<?php

namespace Modules\TikTokShop\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use Modules\TikTokShop\Jobs\SyncTikTokShopOrderJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\TikTokShop\Jobs\SyncTikTokShopProductJob;
use Modules\TikTokShop\Services\TikTokShop;

class TikTokShopController extends Controller
{
    public function webhook()
    {
        $logger   = LogService::logger('tiktokshop-events');
        $requests = $this->request();
        // Validate dữ liệu gửi về từ Webhook Tiktokshop
        $tikTokShopApi       = Service::tikTokShop()->api();
        $type                = $requests->get('type');
        $headerAuthorization = $requests->header('Authorization');
        $authorization       = $tikTokShopApi->makeAuthorization(json_encode($requests->all()));
        // dd($headerAuthorization, $authorization);
        $logger->info('requests', ['data' => $requests->all()]);
        $logger->info('authorization', ['data' => ['headerAuthorization' => $headerAuthorization, 'authorization' => $authorization]]);

        if ($headerAuthorization == $authorization) {
            $shopId = $requests->get('shop_id');

            if (!$store = Store::where(['marketplace_store_id' => $shopId, 'marketplace_code' => Marketplace::CODE_TIKTOKSHOP])->first()) {
                return null;
            }

            $datasHooks = $this->request()->get('data');

            $logger->info('event', ['data' => $datasHooks]);

            switch ($type) {
                case TikTokShop::WEBHOOK_ORDER_STATUS_UPDATED:
                    if ($datasHooks) {
                        $orderId     = data_get($datasHooks, 'order_id');
                        $orderStatus = data_get($datasHooks, 'order_status');
                        $this->dispatch(new SyncTikTokShopOrderJob($store, ['order_id' =>$orderId, 'order_status' => $orderStatus]));
                    }
                    break;
                case TikTokShop::WEBHOOK_PRODUCT_STATUS_UPDATED:
                    if ($datasHooks) {
                        $productId = data_get($datasHooks, 'product_id');
                        $this->dispatch(new SyncTikTokShopProductJob($store, $productId));
                    }
                    break;
                
                default:
                    return null;
                    break;
            }
            return $this->response()->success(['message' => 'success']);
        } else {
            if (config('services.tiktokshop.debug')) {
                return $this->response()->success(['authorization' => $authorization]);
            }
        }
        
    }

}
