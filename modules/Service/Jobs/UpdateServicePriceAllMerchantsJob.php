<?php

namespace Modules\Service\Jobs;

use App\Base\Job;
use Gobiz\Support\RestApiException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Modules\User\Models\User;

class UpdateServicePriceAllMerchantsJob extends Job implements ShouldBeUnique
{
    /**
     * @var string
     */
    public $queue = 'service_price';

    /**
     * @var array
     */
    protected $input;

    /**
     * @var User
     */
    protected $creator;

    /**
     * PublishChangeStockWebhookEventJob constructor
     *
     * @param int $skuId
     */
    public function __construct($input, User $creator)
    {
        $this->input   = $input;
        $this->creator = $creator;
    }

    /**
     * @throws RestApiException
     */
    public function handle()
    {
        \Modules\Service::service()->updateServicePriceAllMerchants($this->input, $this->creator);
    }
}
