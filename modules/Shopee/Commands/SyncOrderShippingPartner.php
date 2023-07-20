<?php

namespace Modules\Shopee\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Collection;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncOrderShippingPartner
{
    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var array
     */
    protected $orderInput;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncOrderShippingPartner constructor
     *
     * @param int $storeId
     * @param array $orderInput
     * @param User $creator
     */
    public function __construct($storeId, array $orderInput, User $creator = null)
    {
        $this->storeId = $storeId;
        $this->orderInput = $orderInput;
        $this->creator = $creator ?: Service::user()->getSystemUserDefault();

        $this->logger = LogService::logger('shopee', [
            'context' => ['store_id' => $storeId, 'order' => $orderInput],
        ]);
    }

    /**
     * @return Order|null|object
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function handle()
    {
        if (empty($this->orderInput['shipping_carrier'])) {
            return null;
        }

        $store = Store::find($this->storeId);

        if (!$order = Service::shopee()->findOrder($store->id, $this->orderInput['order_sn'])) {
            $this->logger->error('CANT_FIND_ORDER');
            return null;
        }

        $shippingPartner = $this->getShippingPartner($store, $this->orderInput['shipping_carrier']);
        if($shippingPartner instanceof ShippingPartner) {
            Service::shopee()->updateOrderShippingPartner($order, $shippingPartner, $this->creator);
            return $order;
        }

        if (!$logistic = $this->findShopeeLogistic($store, $this->orderInput['shipping_carrier'])) {
            //đoạn này do api shopee chỉ trả về "Nhanh", "Hỏa Tốc", "Tiết Kiệm"
            $logistic = Service::shopee()->makeLogisticDataFromName($this->orderInput['shipping_carrier']);
        }

        $shippingPartner = Service::shopee()->makeShippingPartner($order->tenant_id, $logistic);
        Service::shopee()->updateOrderShippingPartner($order, $shippingPartner, $this->creator);

        return $order;
    }

    /**
     * @param Store $store
     * @param $logisticName
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     */
    protected function getShippingPartner(Store $store, $logisticName)
    {
        return ShippingPartner::query()->where('tenant_id', $store->tenant_id)
            ->where('name', trim($logisticName))
            ->where('provider', ShippingPartner::PROVIDER_SHOPEE)
            ->first();
    }

    /**
     * @param Store $store
     * @param $logisticName
     * @return mixed
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    protected function findShopeeLogistic(Store $store,$logisticName)
    {
        $logistics  = Service::shopee()->getLogistic($store);

        return (new Collection($logistics))->firstWhere('logistics_channel_name', $logisticName);
    }
}
