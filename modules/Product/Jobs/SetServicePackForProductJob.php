<?php

namespace Modules\Product\Jobs;

use App\Base\Job;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductEvent;
use Modules\Service\Models\ServicePack;
use Modules\Service\Models\ServicePackPrice;
use Modules\User\Models\User;

class SetServicePackForProductJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'service_pack';

    /** @var Product $product */
    protected $product;
    /** @var ServicePack $servicePack */
    protected $servicePack;
    /** @var User $creator */
    protected $creator;

    /**
     * PublishChangeStockWebhookEventJob constructor
     *
     * @param Product $product
     * @param ServicePack $servicePack
     * @param User $creator
     */
    public function __construct(Product $product, ServicePack $servicePack, User $creator)
    {
        $this->product     = $product;
        $this->servicePack = $servicePack;
        $this->creator     = $creator;
    }

    /**
     */
    public function handle()
    {
        $syncProductServicePrices = [];
        /** @var ServicePackPrice $servicePackPrice */
        foreach ($this->servicePack->servicePackPrices as $servicePackPrice) {
            $syncProductServicePrices[$servicePackPrice->service_price_id] = [
                'tenant_id' => $this->servicePack->tenant_id,
                'service_id' => $servicePackPrice->service_id
            ];
        }
        $this->product->servicePrices()->sync($syncProductServicePrices);
        $this->product->logActivity(ProductEvent::UPDATE_SERVICE, $this->creator, [
            'service_pack' => $this->servicePack
        ]);
    }
}
