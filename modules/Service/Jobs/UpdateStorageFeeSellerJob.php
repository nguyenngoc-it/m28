<?php

namespace Modules\Service\Jobs;

use App\Base\Job;
use Exception;
use Gobiz\Log\LogService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\Eloquent\Collection;
use Modules\Merchant\Models\Merchant;
use Modules\Service\Commands\UpdateStorageFeeSellerStatistic;
use Modules\Service\Commands\UpdateStorageFeeSkuStatistic;
use Modules\Stock\Models\Stock;

class UpdateStorageFeeSellerJob extends Job implements ShouldBeUnique
{
    /**
     * @var string
     */
    public $queue = 'service_price';
    /**
     * @var int
     */
    protected $tenantId;
    /**
     * @var string
     */
    protected $sellerCode;
    /**
     * @var array
     */
    protected $betweenDays = [];

    /** @var Merchant $merchant */
    protected $merchant;

    /**
     * PublishChangeStockWebhookEventJob constructor
     *
     * @param int $tenantId
     * @param string $sellerCode
     * @param array $betweenDays
     */
    public function __construct(int $tenantId, string $sellerCode, array $betweenDays)
    {
        $this->tenantId    = $tenantId;
        $this->sellerCode  = $sellerCode;
        $this->betweenDays = $betweenDays;
        $this->merchant    = Merchant::query()->where([
            'code' => $this->sellerCode,
            'tenant_id' => $this->tenantId
        ])->first();
    }

    /**
     * @return string
     */
    public function uniqueId()
    {
        return 'update_storage_fee_seller_' . $this->merchant->id;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        $query = Stock::query();
        $query->where('stocks.tenant_id', $this->tenantId)
            ->join('skus', 'stocks.sku_id', 'skus.id')
            ->where('skus.merchant_id', $this->merchant->id);
        if (!$closingTime = $this->merchant->closingTimeStorage()) {
            LogService::logger('sku-storage-fee-daily')->error('seller ' . $this->sellerCode . ' closing time is not exists');
            return;
        }

        $query->select('stocks.*')->orderBy('stocks.id')->chunk(100, function (Collection $stocks) use ($closingTime) {
            /** @var Stock $stock */
            foreach ($stocks as $stock) {
                try {
                    (new UpdateStorageFeeSkuStatistic($stock, $closingTime, $this->betweenDays))->handle();
                } catch (Exception $exception) {
                    LogService::logger('sku-storage-fee-daily')->error($exception->getMessage());
                }
            }
        });

        /**
         * Tiến hành truy thu Seller
         */
        (new UpdateStorageFeeSellerStatistic($this->merchant, $this->betweenDays))->handle();
    }
}
