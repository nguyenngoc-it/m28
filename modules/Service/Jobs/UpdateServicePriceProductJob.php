<?php

namespace Modules\Service\Jobs;

use App\Base\Job;
use Gobiz\Support\RestApiException;
use Illuminate\Database\Eloquent\Collection;
use Modules\User\Models\User;

class UpdateServicePriceProductJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'service_price';

    /**
     * @var Collection
     */
    protected $products;

    /**
     * @var User
     */
    protected $creator;

    /**
     * PublishChangeStockWebhookEventJob constructor
     *
     * @param int $skuId
     */
    public function __construct(Collection $products, User $creator)
    {
        $this->products = $products;
        $this->creator  = $creator;
    }

    /**
     * @throws RestApiException
     */
    public function handle()
    {
        \Modules\Service::service()->updateServicePriceProduct($this->products, $this->creator);
    }
}
