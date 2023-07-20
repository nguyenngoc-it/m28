<?php

namespace Modules\Product\Jobs;

use App\Base\Job;
use Gobiz\Support\RestApiException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Modules\Product\Models\Sku;

class PublishSkuChangeStockWebhookEventJob extends Job implements ShouldBeUnique
{
    /**
     * @var string
     */
    public $queue = 'webhook';

    /**
     * @var int
     */
    protected $skuId;

    /**
     * PublishChangeStockWebhookEventJob constructor
     *
     * @param int $skuId
     */
    public function __construct($skuId)
    {
        $this->skuId = $skuId;
    }

    /**
     * @return string
     */
    public function uniqueId()
    {
        return $this->skuId;
    }

    /**
     * @throws RestApiException
     */
    public function handle()
    {
        $sku = Sku::find($this->skuId);
        $sku->webhook()->changeStock()->publish();
    }
}
