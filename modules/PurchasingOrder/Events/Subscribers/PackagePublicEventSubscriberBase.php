<?php

namespace Modules\PurchasingOrder\Events\Subscribers;

use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Document\Models\ImportingBarcode;
use Modules\PurchasingManager\Models\PurchasingService;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Psr\Log\LoggerInterface;

abstract class PackagePublicEventSubscriberBase
{
    /** @var array $eventName */
    protected $appliedEvents = [
        'PACKAGE_CREATE',
        'PACKAGE_LINK_ORDER',
        'PACKAGE_UNLINK_ORDER',
        'PACKAGE_CHECKING',
        'PACKAGE_UPDATE_ITEMS',
        'PACKAGE_CHANGE_TRANSPORT_STATUS',
        'PACKAGE_UPDATE_WEIGHT_VOLUME',
    ];
    /** @var array $payload */
    protected $payload = [];
    /** @var PurchasingOrder */
    protected $purchasingOrder;
    /** @var array */
    protected $products;
    /** @var LoggerInterface $logger */
    protected $logger;
    /** @var bool $validData */
    protected $validData = false;
    protected $event;
    protected $packageCode;

    public function __construct(array $inputs)
    {
        $this->logger          = LogService::logger('m6-package-subscriber', [
            'context' => [
                'event' => array_merge($inputs, [
                    'payload' => Arr::only(Arr::get($inputs, 'payload', []), ['tenant', 'package.code', 'package.status_transport']),
                ]),
            ],
        ]);
        $this->payload         = array_merge(
            [
                'event' => Arr::get($inputs, 'event')
            ],
            Arr::get($inputs, 'payload', [])
        );
        $this->purchasingOrder = $this->getPurchasingOrder();
        $this->products        = Arr::get($this->payload, 'package_items', []);
        $this->event           = Arr::get($this->payload, 'event');
        $this->packageCode     = Arr::get($this->payload, 'package.code');
        if ($this->event == 'PACKAGE_UNLINK_ORDER' && $this->purchasingOrder) {
            $this->unlinkOrder();
        }
        $this->validData = $this->validateData();
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
            $this->logger->error('payload_empty');
            return false;
        }

        if (!in_array($event, $this->appliedEvents)) {
            $this->logger->error('event_not_allow');
            return false;
        }

        if (empty($this->purchasingOrder)) {
            $this->logger->error('purchasing_order_not_found');
            return false;
        }

        if ($this->packageWasImported()) {
            $this->logger->error('package_was_imported');
            return false;
        }

        return true;
    }

    /**
     * @return PurchasingOrder|null|mixed
     */
    protected function getPurchasingOrder()
    {
        $tenant    = Arr::get($this->payload, 'tenant');
        $orderCode = Arr::get($this->payload, 'order.code');
        if (empty($tenant)) {
            return null;
        }
        /** @var PurchasingService|null $purchasingService */
        $purchasingService = PurchasingService::query()->where([
            'code' => $tenant,
            'active' => true
        ])->first();
        if (empty($purchasingService)) {
            return null;
        }

        return PurchasingOrder::query()->where([
            'code' => $orderCode,
            'purchasing_service_id' => $purchasingService->id
        ])->first();
    }

    /**
     * Xoá kiện khỏi đơn trong th kiện M6 bị xoá
     */
    protected function unlinkOrder()
    {
        PurchasingPackage::query()->where([
            'code' => $this->packageCode,
            'purchasing_order_id' => $this->purchasingOrder->id
        ])->delete();
    }

    /**
     * Kiện đã nằm trong chứng từ nhập
     *
     * @return boolean
     */
    protected function packageWasImported()
    {
        $purchasingPackage = PurchasingPackage::query()->where([
            'code' => $this->packageCode,
            'purchasing_order_id' => $this->purchasingOrder->id
        ])->first();
        if ($purchasingPackage) {
            $importingBarcode = ImportingBarcode::query()->where([
                'type' => ImportingBarcode::TYPE_PACKAGE_CODE,
                'object_id' => $purchasingPackage->id
            ])->first();
            if ($importingBarcode) {
                return true;
            }
        }
        return false;
    }
}
