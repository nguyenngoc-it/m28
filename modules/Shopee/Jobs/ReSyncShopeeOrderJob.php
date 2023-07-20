<?php

namespace Modules\Shopee\Jobs;

use App\Base\Job;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Shopee\Services\Shopee;

class ReSyncShopeeOrderJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'shopee';

    /**
     * @var int
     */
    protected $shopId;

    /**
     * @var array
     */
    protected $orderInputs;

    /**
     * SyncShopeeOrdersJob constructor
     *
     * @param int $shopId
     * @param array $orderInputs
     */
    public function __construct($shopId, array $orderInputs)
    {
        $this->shopId      = $shopId;
        $this->orderInputs = $orderInputs;
    }

    public function handle()
    {
        $logger = LogService::logger('resync-shopee', [
            'context' => ['shop_id' => $this->shopId, 'orderInputs' => $this->orderInputs],
        ]);

        $orderCode   = Arr::get($this->orderInputs, 'order_sn');

        $logger->info('start check re sync order');

        $order = Order::query()->where('marketplace_code', Marketplace::CODE_SHOPEE)
            ->where('marketplace_store_id', $this->shopId)
            ->where('code', $orderCode)->first();
        if($order instanceof Order) {
            return;
        }

        $logger->info('start re sync order');

        $orderInputs = [
            [
                'order_sn' => $orderCode,
                'order_status' => '', //api danh sách đơn shopee không trả về trạng thái nên để trống,
            ],
        ];
        $orders = Service::shopee()->syncOrders($this->shopId, $orderInputs);

        $message = (empty($orders)) ? 'error' : 'success';

        $logger->info('end re sync order '.$message);
    }
}
