<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Exception;
use Modules\InvalidOrder\Models\InvalidOrder;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Commands\CreateBashOrder;

class CreatingBashMerchantOrder extends Job
{
    protected $cachedBashOrders;

    /**
     * CalculateServiceAmount constructor
     *
     * @param array $cachedBashOrders
     */
    public function __construct(array $cachedBashOrders)
    {
        $this->cachedBashOrders = $cachedBashOrders;
    }

    public function handle()
    {
        foreach ($this->cachedBashOrders as $cachedBashOrder) {
            try {
                (new CreateBashOrder($cachedBashOrder))->handle();
            } catch (Exception $exception) {
                $merchantId = $cachedBashOrder['merchant_id'];
                $merchant   = Merchant::find($merchantId);
                InvalidOrder::query()->firstOrCreate(
                    [
                        'tenant_id' => $merchant->tenant->id,
                        'source' => InvalidOrder::SOURCE_OTHER,
                        'code' => $cachedBashOrder['code'],
                    ],
                    [
                        'payload' => [
                            'input' => $cachedBashOrder,
                        ],
                        'error_code' => InvalidOrder::ERROR_TECHNICAL,
                        'errors' => ['bash_order' => $exception->getMessage()],
                        'creator_id' => $cachedBashOrder['creator_id'],
                    ]
                );
            }
        }
    }
}
