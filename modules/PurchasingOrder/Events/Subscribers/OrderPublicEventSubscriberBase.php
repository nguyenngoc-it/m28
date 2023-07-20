<?php

namespace Modules\PurchasingOrder\Events\Subscribers;

use Gobiz\Log\LogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\PurchasingManager\Models\PurchasingService;
use Psr\Log\LoggerInterface;

abstract class OrderPublicEventSubscriberBase
{
    /** @var array $eventName */
    protected $appliedEvents = [
        'ORDER_CREATE',
        'ORDER_PACKAGE_UPDATE',
        'ORDER_PRODUCT_UPDATE',
        'ORDER_PRODUCT_UPDATE_PURCHASED_QUANTITY',
        'ORDER_PRODUCT_UPDATE_RECEIVED_QUANTITY',
        'ORDER_STATUS_UPDATE',
        'FEE_CHANGED',
        'ORDER_UPDATE_MERCHANT_SHIPPING_COST',
        'PACKAGE_CHANGE_COST_OF_SHIPPING',
        'ORDER_UPDATE_EXCHANGE_RATE',
    ];
    /** @var array $payload */
    protected $payload = [];
    /** @var PurchasingAccount[]|Collection $purchasingAccount */
    protected $purchasingAccounts;
    protected $isShipment = false;
    protected $receiverLocations = [];
    /** @var LoggerInterface $logger */
    protected $logger;
    /** @var bool $validData */
    protected $validData = false;

    public function __construct(array $inputs)
    {
        $this->logger             = LogService::logger('m2-order-subscriber', [
            'context' => [
                'event' => array_merge($inputs, [
                    'payload' => Arr::only(Arr::get($inputs, 'payload', []), ['id', 'code', 'status']),
                ]),
            ],
        ]);
        $this->payload            = array_merge(
            [
                'tenant' => Arr::get($inputs, 'tenant'),
                'event' => Arr::get($inputs, 'event')
            ],
            Arr::get($inputs, 'payload', [])
        );
        $this->receiverLocations  = Arr::get($this->payload, 'address.location');
        $this->purchasingAccounts = $this->getPurchasingAccounts();
        $this->isShipment         = Arr::get($this->payload, 'isShipment', false);
        $this->validData          = $this->validateData();
    }

    /**
     * @return bool
     */
    public function isValidData(): bool
    {
        return $this->validData;
    }

    /**
     * @return bool
     */
    protected function validateData()
    {
        $event = Arr::get($this->payload, 'event');
        if (empty($this->payload)) {
            $this->logger->error('payload_empty', [$this->payload['code']]);
            return false;
        }

        if (!in_array($event, $this->appliedEvents)) {
            $this->logger->error('event_not_allow', [$this->payload['code']]);
            return false;
        }

        if (empty($this->receiverLocations)) {
            $this->logger->error('receiver_location_not_found', [$this->payload['code']]);
            return false;
        }

        if ($this->purchasingAccounts->count() == 0) {
            $this->logger->error('purchasing_account_not_found', [$this->payload['code']]);
            return false;
        }

        return true;
    }

    protected function getPurchasingAccounts()
    {
        $tenant           = Arr::get($this->payload, 'tenant');
        $customerUsername = Arr::get($this->payload, 'customer.username');
        $customerEmail    = Arr::get($this->payload, 'customer.email');
        if (empty($tenant)) {
            return collect([]);
        }
        /** @var PurchasingService|null $purchasingService */
        $purchasingService = PurchasingService::query()->where([
            'code' => $tenant,
            'active' => true
        ])->first();
        if (empty($purchasingService)) {
            return collect([]);
        }
        return $purchasingService->purchasingAccounts->filter(function (PurchasingAccount $purchasingAccount) use ($customerUsername, $customerEmail) {
            return in_array($purchasingAccount->username, [$customerUsername, $customerEmail]);
        });
    }
}
