<?php

namespace Modules\Shopee\Commands;
use Gobiz\Log\LogService;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;

class FindSkusByVariations
{
    /**
     * @var Store
     */
    protected $store;

    /**
     * @var array
     */
    protected $models = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Store $store
     * @param array $models
     */
    public function __construct(Store $store, array $models)
    {
        $this->store  = $store;
        $this->models = $models;

        $this->logger = LogService::logger('find_skus_by_variations', [
            'context' => compact('store', 'models'),
        ]);
    }

    /**
     * @return Sku[]
     */
    public function handle()
    {
        $results = [];
        foreach ($this->models as $model) {
            $modelId  = $model['model_id'];
            $modelSku = $model['model_sku'];

            $storeSku  = Service::store()->getStoreSkuOnSell($this->store, $modelId, $modelSku);
            if ($storeSku) {
                $results[$modelId] = $storeSku->sku;
            }
        }

        return $results;
    }
}
