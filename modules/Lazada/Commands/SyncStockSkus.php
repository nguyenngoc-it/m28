<?php

namespace Modules\Lazada\Commands;

use Gobiz\Log\LogService;
use Modules\Lazada\Jobs\SyncStockSkuJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;

class SyncStockSkus
{
    const PER_PAGE = 50;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var int
     */
    protected $merchantId;

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $filterUpdateTime = true;

    protected $api;

    protected $type;

    /**
     * @param $store
     * @param $merchantId
     * @param $filterUpdateTime
     * @param $type
     */
    public function __construct($store, $merchantId, $filterUpdateTime = true, $type)
    {
        $this->type = $type;
        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
        $this->api = Service::lazada()->api();
        $this->logger = LogService::logger('m28-sync-products', [
            'context' => ['storeId' => $store->id, 'merchant_id' => $merchantId, 'filterUpdateTime' => $filterUpdateTime],
        ]);
    }

    /**
     * @param Store $store
     * @return int
     */
    protected function getLastUpdateTime(Store $store)
    {
        $lastUpdateTime = intval($store->getSetting(Store::SETTING_LAZADA_PRODUCT_LAST_UPDATED_AT));
        $maxTime = time() - 1295400; //thời gian lọc tối đa là từ 15 ngày trước
        if ($lastUpdateTime < $maxTime) {
            $lastUpdateTime = $maxTime;
        }

        return $lastUpdateTime;
    }

    /**
     * @param $type
     * @return void
     */
    public function updateSettingStore($type)
    {
        $settings = $this->store->getAttribute('settings');
        $sync = [
            'type_sync' => $type
        ];
        $typeSync = array_merge($settings, $sync);
        $this->store->settings = $typeSync;
        $this->store->save();
    }

    public function fetchSkus()
    {
        $type = $this->type;
        $this->updateSettingStore($type);
        Sku::query()->select('skus.*')
            ->join('store_skus', 'store_skus.sku_id', '=', 'skus.id')
            ->where('skus.merchant_id', $this->merchantId)
            ->where('skus.tenant_id', $this->store->tenant_id)
            ->where('store_skus.store_id', $this->store->id)
            ->whereNotNull('skus.sku_id_origin')
            ->chunkById(100, function ($skus) {
                foreach ($skus as $sku) {
                    dispatch(new SyncStockSkuJob($sku, $this->store));
                }
            });


    }

    /**
     * @return void|null
     */
    public function handle()
    {
        $this->logger->info($this->store);
        if (!$this->store || $this->store->marketplace_code != Marketplace::CODE_LAZADA) {
            $this->logger->info('Store invalid');
            return null;
        }

        $lastUpdateTime = $this->getLastUpdateTime($this->store);
        $this->logger->info($lastUpdateTime);
        $this->fetchSkus();

    }


}
