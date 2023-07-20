<?php

namespace Modules\Topship\Commands;

use Gobiz\Log\LogService;
use InvalidArgumentException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncTopshipFreightBill
{
    /**
     * @var array
     */
    protected $fulfillment;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncTopshipFreightBill constructor
     *
     * @param array $fulfillment
     * @param User $creator
     */
    public function __construct(array $fulfillment, User $creator = null)
    {
        $this->fulfillment = $fulfillment;
        $this->creator = $creator ?: Service::user()->getSystemUserDefault();

        $this->logger = LogService::logger('topship', [
            'context' => [
                'fulfillment' => $fulfillment,
            ],
        ]);
    }

    /**
     * @return FreightBill|false
     */
    public function handle()
    {
        if (!$status = Service::topship()->mapFreightBillStatus($this->fulfillment['shipping_state'])) {
            $this->logger->info('CANT_MAPPING_FREIGHT_BILL_STATUS');
            return false;
        }

        if (!$freightBill = $this->findFreightBill()) {
            $this->logger->error('CANT_FIND_FREIGHT_BILL');
            throw new InvalidArgumentException("FreightBill {$this->fulfillment['shipping_code']} not found");
        }

        if (!$freightBill->currentOrderPacking) {
            $this->logger->error('CANT_FIND_ORDER_PACKING_FOR_FREIGHT_BILL');
            return false;
        }

        return $freightBill->changeStatus($status, $this->creator);
    }

    /**
     * @return FreightBill|object|null
     */
    protected function findFreightBill()
    {
        return FreightBill::query()
            ->where('freight_bill_code', $this->fulfillment['shipping_code'])
            ->whereHas('shippingPartner', function ($query) {
                $query->where('provider', ShippingPartner::PROVIDER_TOPSHIP);
            })
            ->first();
    }
}
