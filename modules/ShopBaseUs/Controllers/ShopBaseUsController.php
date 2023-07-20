<?php

namespace Modules\ShopBaseUs\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use Modules\ShopBaseUs\Jobs\SyncShopBaseUsOrderJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\ShopBaseUs\Jobs\SyncShopBaseUsProductJob;
use Modules\ShopBaseUs\Services\ShopBaseUs;

class ShopBaseUsController extends Controller
{
    public function webhook()
    {
        $logger   = LogService::logger('shopbaseus-events');
        $requests = $this->request();
        // Validate dữ liệu gửi về từ Webhook shopBaseUs
        $shopBaseUsApi       = Service::shopBaseUs()->api();

        $headerAuthorization = $requests->header('X-Shopbase-Hmac-Sha256');
        $type                = $requests->header('X-Shopbase-Entity-Type');
        $shopDomain          = $requests->header('X-Shopbase-Shop-Domain');

        // Lấy thông tin store
        $shopDomain = str_replace(".onshopbase.com", "", $shopDomain);
        $store = Store::where([
            ['marketplace_code', Marketplace::CODE_SHOPBASE],
            ['settings->shop_name', $shopDomain],
        ])->first();

        if ($store) {

            $authorization = $shopBaseUsApi->makeAuthorization(json_encode($requests->all()), $store->getSetting('shared_secret'));
            // dd($headerAuthorization, $authorization);
            $verified = hash_equals($authorization, $headerAuthorization);
            $logger->info('requests', ['data' => $requests->all()]);
            $logger->info('headers', ['data' => [
                'X-Shopbase-Entity-Type' =>  $type,  
                'X-Shopbase-Shop-Domain' =>  $shopDomain,  
            ]]);
            $logger->info('authorization', ['data' => ['headerAuthorization' => $headerAuthorization, 'authorization' => $authorization, 'verified' => $verified]]);

            if ($verified) {

                $datasHooks = $requests->all();

                $logger->info('event', ['data' => $datasHooks]);

                switch ($type) {
                    case ShopBaseUs::WEBHOOK_ORDER_STATUS_UPDATED:
                        if ($datasHooks) {
                            $orderId = data_get($datasHooks, 'id');
                            $this->dispatch(new SyncShopBaseUsOrderJob($store, ['order_id' =>$orderId]));
                        }
                        break;
                    case ShopBaseUs::WEBHOOK_PRODUCT_STATUS_UPDATED:
                        if ($datasHooks) {
                            $productId = data_get($datasHooks, 'id');
                            $this->dispatch(new SyncShopBaseUsProductJob($store, $productId));
                        }
                        break;
                    case ShopBaseUs::WEBHOOK_FULFILLMENT_STATUS_UPDATED:
                        if ($datasHooks) {
                            $orderId = data_get($datasHooks, 'order_id');
                            $this->dispatch(new SyncShopBaseUsOrderJob($store, ['order_id' =>$orderId]));
                        }
                        break;	

                    
                    default:
                        return null;
                        break;
                }
                return $this->response()->success(['message' => 'success']);
            } else {
                if (config('services.shopbaseus.debug')) {
                    return $this->response()->success(['authorization' => $authorization]);
                }
            }
        }
        
    }

}
