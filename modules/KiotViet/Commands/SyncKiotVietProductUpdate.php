<?php

namespace Modules\KiotViet\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Currency\Models\Currency;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuPrice;
use Modules\Product\Services\ProductEvent;
use Modules\Product\Services\SkuEvent;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncKiotVietProductUpdate
{
    /**
     * @var Store
     */
    protected $store;

    /**
     * @var array
     */
    protected $input;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncKiotVietOrderJob constructor
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của KiotViet api /orders/detail
     * @param User $creator
     */
    public function __construct(Store $store, array $input)
    {
		$this->store   = $store;
		$this->input   = $input;
		$this->creator = Service::user()->getSystemUserDefault();

        $this->logger = LogService::logger('KiotViet', [
            'context' => ['shop_id' => $store->marketplace_store_id],
        ]);
    }

    /**
     * @return Order
     * @throws ValidationException
     * @throws WorkflowException
     */
    public function handle()
    {
        return $this->syncProductUpdate();
    }

    protected function syncProductUpdate() 
    {
    	$this->logger->info('data product update', $this->input);
    }


}