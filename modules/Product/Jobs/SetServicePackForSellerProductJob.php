<?php

namespace Modules\Product\Jobs;

use App\Base\Job;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Service\Models\ServicePack;
use Modules\User\Models\User;

class SetServicePackForSellerProductJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'service_pack';

    protected $merchantId;
    /** @var User $creator */
    protected $creator;

    /**
     * PublishChangeStockWebhookEventJob constructor
     *
     * @param $merchantId
     * @param User $creator
     */
    public function __construct($merchantId, User $creator)
    {
        $this->merchantId = $merchantId;
        $this->creator    = $creator;
    }

    /**
     */
    public function handle()
    {
        $merchant    = Merchant::find($this->merchantId);
        $servicePack = ServicePack::find($merchant->service_pack_id);
        /** @var Product $product */
        foreach ($merchant->myProducts as $product) {
            dispatch(new SetServicePackForProductJob($product, $servicePack, $this->creator));
        }
    }
}
