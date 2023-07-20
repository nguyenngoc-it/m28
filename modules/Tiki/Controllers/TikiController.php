<?php

namespace Modules\Tiki\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Tiki\Commands\SyncTikiProduct;
use Modules\Tiki\Jobs\SyncTikiOrderJob;
use Modules\Tiki\Jobs\SyncTikiProductJob;
use Modules\Tiki\Services\Tiki;
use Modules\Marketplace\Services\Marketplace;
use Modules\Service;
use Modules\Store\Models\Store;
use Illuminate\Support\Str;
use Modules\Tiki\Commands\SyncTikiOrder;

class TikiController extends Controller
{
    public function webhook()
    {
        $logger   = LogService::logger('tiki-events');
        $requests = $this->request();
        $orderId = $requests->get('order_id');
        $marketPlaceId = $requests->get('marketplace_store_id');

        // Lấy Store 
        $store = Store::where([
            'marketplace_code'     => Marketplace::CODE_TIKI,
            'marketplace_store_id' => $marketPlaceId,
        ])->get()->first();
        
        if ($store) {
            $paramsRequest = [
                'order_id' => $orderId
            ];
            dispatch(new SyncTikiOrderJob($store, $paramsRequest));
        }
        
    }

}
