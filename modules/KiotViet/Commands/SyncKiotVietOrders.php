<?php

namespace Modules\KiotViet\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;
use Modules\KiotViet\Jobs\SyncKiotVietOrderJob;
use Modules\KiotViet\Services\KiotViet;

class SyncKiotVietOrders
{
    const PER_PAGE = 10;

    /**
     * @var int
     */
    protected $shopId;

    /**
     * @var int
     */
    protected $merchantId;

    /**
     * @var Merchant
     */
    protected $merchant;


    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $filterUpdateTime = true;

    protected $api;

    /**
     * SyncKiotVietOrders constructor.
     * @param $store
     * @param User|null $creator
     * @param bool $filterUpdateTime
     */
    public function __construct(Store $store, User $creator = null, $filterUpdateTime = true)
    {
        $this->store            = $store;
        $this->api              = Service::kiotviet()->api();
        $this->filterUpdateTime = $filterUpdateTime;
        $this->dt               = Carbon::now();

        $this->logger = LogService::logger('kiotviet-sync-orders', [
            'context' => ['store_id' => $this->store->id, 'merchant_id' => $this->store->merchant_id, 'filterUpdateTime' => $filterUpdateTime],
        ]);
    }

    /**
     * @param Store $store
     * @return int
     */
    protected function getLastUpdateTime(Store $store)
    {
        $lastUpdateTime = Carbon::parse($store->getSetting(Store::SETTING_KIOTVIET_PRODUCT_LAST_UPDATED_AT, $this->dt->toDateTimeString()));
        $maxTime        = $this->dt->subDays(15); //thời gian lọc tối đa là từ 15 ngày trước
        if ($maxTime->gte($lastUpdateTime)) {
            $lastUpdateTime = $maxTime;
        }

        return $lastUpdateTime;
    }

    /**
     * @return array
     * @throws RestApiException
     */
    public function handle()
    {
        $this->logger->info($this->store);
        if (!$this->store || $this->store->marketplace_code != Marketplace::CODE_KIOTVIET) {
            $this->logger->info('Store invalid');
            return null;
        }

        $lastUpdateTime = $this->getLastUpdateTime($this->store);
        $orders = [];

        $this->logger->info($lastUpdateTime);

        $this->fetchData(0, $orders, $lastUpdateTime);

        $this->logger->info(json_encode($orders));
        if(empty($orders)) {
            $this->logger->info(' empty orders');
            return [];
        }
    }


    /**
     * @param int $paginationOffset
     * @param array $items
     * @param integer $lastUpdateTime
     * @throws RestApiException
     */
    protected function fetchData($paginationOffset = 0, &$items = [], $lastUpdateTime = null)
    {
        $filter = [
            'currentItem' => $paginationOffset,
            'pageSize'    => self::PER_PAGE,
            'createdDate' => Carbon::now()->format('Y-m-d') // Lấy order mới tạo hôm nay
        ];

        if($this->filterUpdateTime) {
            $filter = array_merge($filter, [
                'lastModifiedFrom' => $lastUpdateTime
            ]);
        }

        // Get Orders
        $orders    = $this->api->getOrders($filter, $this->store);
        $items     = array_merge($items, $orders->getData('data', []));
        $itemTotal = $orders->getData('total', 0);
        $offset    = $paginationOffset + self::PER_PAGE;

        foreach ($items as $item) {
            //
            $orderId = data_get($item, 'id', 0);
            if ($orderId) {
                $order     = $this->api->getOrder(['order_id' => $orderId], $this->store);
                $dataOrder = $order->getData();
                $dataOrder['__event'] = KiotViet::WEBHOOK_ORDER_STATUS_UPDATE;
                dispatch(new SyncKiotVietOrderJob($this->store, $dataOrder));
            }
        }

        // Get Invoices
        $invoices     = $this->api->getInvoices($filter, $this->store);
        $itemInvoices = $invoices->getData('data', []);
        $itemTotal    = $invoices->getData('total', 0);
        $offset       = $paginationOffset + self::PER_PAGE;

        foreach ($itemInvoices as $item) {
            //
            $invoiceId = data_get($item, 'id', 0);
            if ($invoiceId) {
                $invoice     = $this->api->getInvoiceDetail($invoiceId, $this->store);
                $dataInvoice = $invoice->getData();
                $dataInvoice['__event'] = KiotViet::WEBHOOK_INVOICE_STATUS_UPDATE;
                dispatch(new SyncKiotVietOrderJob($this->store, $dataInvoice));
            }
        }

        if($itemTotal > $offset) {
            $this->fetchData($offset , $items, $lastUpdateTime);
        }
    }

}
