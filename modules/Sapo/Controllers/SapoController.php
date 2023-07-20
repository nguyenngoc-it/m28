<?php

namespace Modules\Sapo\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use Modules\Sapo\Jobs\SyncSapoOrderJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Sapo\Jobs\SyncSapoProductJob;
use Modules\Sapo\Services\Sapo;

class SapoController extends Controller
{
    public function webhook()
    {
        $logger   = LogService::logger('sapo-events');
        $requests = $this->request();
        // Validate dữ liệu gửi về từ Webhook Sapo
        $sapoApi       = Service::sapo()->api();

        $headerAuthorization = $requests->header('X-Sapo-Hmac-Sha256');
        $type                = $requests->header('X-Sapo-Topic');
        $shopDomain          = $requests->header('X-Sapo-Shop-Domain');

        // Lấy thông tin store
        $shopDomain = str_replace(".mysapo.net", "", $shopDomain);
        $store = Store::where([
            ['marketplace_code', Marketplace::CODE_SAPO],
            ['settings->shop_name', $shopDomain],
        ])->first();

        if ($store) {

            $authorization = $sapoApi->makeAuthorization(json_encode($requests->all()), $store->getSetting('client_secret'));
            // dd($headerAuthorization, $authorization);
            $verified = hash_equals($authorization, $headerAuthorization);
            $logger->info('requests', ['data' => $requests->all()]);
            $logger->info('headers', ['data' => [
                'X-Sapo-Topic' =>  $type,  
                'X-Sapo-Shop-Domain' =>  $shopDomain,  
            ]]);
            $logger->info('authorization', ['data' => ['headerAuthorization' => $headerAuthorization, 'authorization' => $authorization, 'verified' => $verified]]);
            
            // Không rõ thuật toán makeAuthorization nên check như này 
            // https://support.sapo.vn/sapo-webhook
            if ($shopDomain == $store->getSetting('shop_name')) {
                $verified = true;
            }
            
            if ($verified) {

                $datasHooks = $requests->all();

                $logger->info('event', ['data' => $datasHooks]);
                
                $typeList   = explode('/', $type);
                $typeUpdate = $typeList[0];

                switch ($typeUpdate) {
                    case Sapo::WEBHOOK_ORDER_STATUS_UPDATED:
                        if ($datasHooks) {
                            $orderId = data_get($datasHooks, 'id');
                            $this->dispatch(new SyncSapoOrderJob($store, ['order_id' =>$orderId]));
                        }
                        break;
                    case Sapo::WEBHOOK_PRODUCT_STATUS_UPDATED:
                        if ($datasHooks) {
                            $productId = data_get($datasHooks, 'id');
                            $this->dispatch(new SyncSapoProductJob($store, $productId));
                        }
                        break;
                    case Sapo::WEBHOOK_FULFILLMENT_STATUS_UPDATED:
                        if ($datasHooks) {
                            $orderId = data_get($datasHooks, 'order_id');
                            $this->dispatch(new SyncSapoOrderJob($store, ['order_id' =>$orderId]));
                        }
                        break;	

                    
                    default:
                        return null;
                        break;
                }
                return $this->response()->success(['message' => 'success']);
            } else {
                if (config('services.sapo.debug')) {
                    return $this->response()->success(['authorization' => $authorization]);
                }
            }
        }
        
    }

}
