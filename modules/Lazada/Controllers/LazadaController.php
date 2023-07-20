<?php

namespace Modules\Lazada\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Lazada\Commands\SyncLazadaProduct;
use Modules\Lazada\Jobs\SyncLazadaOrderJob;
use Modules\Lazada\Jobs\SyncLazadaProductJob;
use Modules\Lazada\Services\Lazada;
use Modules\Marketplace\Services\Marketplace;
use Modules\Service;
use Modules\Store\Models\Store;
use Illuminate\Support\Str;
class LazadaController extends Controller
{
    public function webhook()
    {
        $logger   = LogService::logger('lazada-events');
        $requests = $this->request();
        // Validate dữ liệu gửi về từ Webhook Lazada
        $lazadaApi           = Service::lazada()->api();
        $messType            = $requests->get('message_type');
        $headerAuthorization = $requests->header('Authorization');
        $authorization       = $lazadaApi->makeAuthorization(json_encode($requests->all()));
        // dd($headerAuthorization, $authorization);
        $logger->info('requests', ['data' => $requests->all()]);
        $logger->info('authorization', ['data' => ['headerAuthorization' => $headerAuthorization, 'authorization' => $authorization]]);

        if ($headerAuthorization == $authorization) {

            $sellerId = $requests->get('seller_id');

            if (!$store = Store::where(['marketplace_store_id' => $sellerId, 'marketplace_code' => Marketplace::CODE_LAZADA])->first()) {
                return null;
            }

            $datasHooks = $this->request()->get('data');

            $logger->info('event', ['data' => $datasHooks]);

            switch ($messType) {
                case Lazada::WEBHOOK_ORDER_STATUS_UPDATE:
                case Lazada::WEBHOOK_ORDER_STATUS_REVERSE:
                    if ($datasHooks) {
                        $orderId     = data_get($datasHooks, 'trade_order_id');
                        $orderStatus = data_get($datasHooks, 'order_status');
                        if ($messType == Lazada::WEBHOOK_ORDER_STATUS_REVERSE) {
                            $reverseStatus = data_get($datasHooks, 'reverse_status');
                            if ($reverseStatus == Lazada::WEBHOOK_REVERSE_STATUS_CANCEL_SUCCESS) {
                                $orderStatus = Lazada::ORDER_STATUS_CANCELED;
                            }
                        }
                        $this->dispatch(new SyncLazadaOrderJob($store, ['order_id' =>$orderId, 'order_status' => $orderStatus]));
                    }
                    break;

                case Lazada::WEBHOOK_PRODUCT_STATUS_CREATED:
                case Lazada::WEBHOOK_PRODUCT_STATUS_UPDATED:
                    if ($datasHooks) {
                        $productId = data_get($datasHooks, 'item_id');
                        $this->dispatch(new SyncLazadaProductJob($store, $productId));
                    }
                    break;
                
                default:
                    return null;
                    break;
            }
            return $this->response()->success(['message' => 'success']);

        } else {
            if (config('services.lazada.debug')) {
                return $this->response()->success(['authorization' => $authorization]);
            }
        }
    }

}
