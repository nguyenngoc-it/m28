<?php

namespace Modules\KiotViet\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use InvalidArgumentException;
use Modules\KiotViet\Services\KiotViet;
use Modules\Store\Models\Store;
use Modules\Marketplace\Services\Marketplace;
use Modules\KiotViet\Jobs\SyncKiotVietOrderJob;
use Modules\KiotViet\Jobs\SyncKiotVietProductJob;
use Modules\Service;

class KiotVietController extends Controller
{
    public function webhook()
    {
        $headerHook      = $this->request()->header('X-Webhook-Event');
        $dataEventHeader = collect(explode('_', $headerHook));
        $eventName       = $dataEventHeader->first();
        $retailerID      = $dataEventHeader->last();

        $logger = LogService::logger('kiotviet-events');

        $logger->info('event', ['headerHook' => $headerHook]);

        if (!$store = Store::where(['marketplace_store_id' => $retailerID, 'marketplace_code' => Marketplace::CODE_KIOTVIET])->first()) {
            return null;
        }

        $datasHooks = $this->request()->get('Notifications');

        $logger->info('event', ['data' => $datasHooks]);

        // dd($store);

        switch ($eventName) {
            case KiotViet::WEBHOOK_PRODUCT_STATUS_UPDATE: {
                
                if ($datasHooks) {
                    foreach($datasHooks as $data) {
                        foreach ($data['Data'] as $dataProduct) {
                            $kiotVietProductId = data_get($dataProduct, 'Id', 0);
                            $this->dispatch(new SyncKiotVietProductJob($store, $kiotVietProductId));
                        }
                    }
                }

                return 'aloha';

            }

            case KiotViet::WEBHOOK_ORDER_STATUS_UPDATE:
            case KiotViet::WEBHOOK_INVOICE_STATUS_UPDATE: {

                if ($datasHooks) {
                    foreach($datasHooks as $data) {
                        foreach ($data['Data'] as $dataOrder) {
                            $orderId = data_get($dataOrder, 'Id', 0);
                            if ($orderId) {
                                if ($eventName == KiotViet::WEBHOOK_ORDER_STATUS_UPDATE) {
                                    $order = Service::kiotviet()->api()->getOrder(['order_id' => $orderId], $store);
                                }
                                elseif ($eventName == KiotViet::WEBHOOK_INVOICE_STATUS_UPDATE) {
                                    $order = Service::kiotviet()->api()->getInvoiceDetail($orderId, $store);
                                } else {
                                    return null;
                                }
                                $dataOrder = $order->getData();
                                $dataOrder['__event'] = $eventName;
                                $this->dispatch(new SyncKiotVietOrderJob($store, $dataOrder));
                            }
                        }
                    }
                }

                return 'aloha';
            }

            default:
                return null;
        }
    }
}
