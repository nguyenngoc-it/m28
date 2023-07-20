<?php

namespace Modules\Shopee\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;
use Modules\Marketplace\Services\Marketplace;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Shopee\Services\Shopee;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncShopeeOrders
{
    /**
     * @var int
     */
    protected $shopId;

    /**
     * @var array
     */
    protected $orderInputs;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Collection
     */
    protected $shopeeLogistics;

    /**
     * SyncShopeeOrders constructor
     *
     * @param int $shopId
     * @param array $orderInputs = [['order_sn' => 'xxx', 'order_status' => 'xxx'], ...]
     * @param User|null $creator
     */
    public function __construct($shopId, array $orderInputs, User $creator = null)
    {
        $this->shopId = $shopId;
        $this->orderInputs = $orderInputs;
        $this->creator = $creator ?: Service::user()->getSystemUserDefault();

        $this->logger = LogService::logger('shopee', [
            'context' => ['shop_id' => $shopId, 'orderInputs' => $orderInputs],
        ]);
    }

    /**
     * @return Order[]|null
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function handle()
    {
        $stores = $this->getStores();

        if (empty($stores)) {
            $this->logger->error('STORE_NOT_FOUND_OR_NOT_ACTIVE');
            return null;
        }

        $orders = [];
        foreach ($stores as $store) {
            $shopeeOrders = $this->fetchShopeeOrders($store);

            foreach ($shopeeOrders as $shopeeOrder) {
                try {

                    $input = array_merge($shopeeOrder, [
                        'logistic' => $this->getLogistic($store, $shopeeOrder['shipping_carrier']),
                    ]);

                    $order    = Service::shopee()->syncOrder($store, $input, $this->creator);
                    if($order instanceof Order) {
                        $orders[] = $order;
                    }
                } catch (ValidationException $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
        }

        return $orders;
    }

    /**
     * @return Collection
     */
    protected function getStores()
    {
        return Store::query()->where([
            'marketplace_code' => Marketplace::CODE_SHOPEE,
            'marketplace_store_id' => $this->shopId,
            'status' => Store::STATUS_ACTIVE,
        ])->get();
    }

    /**
     * @param Store $store
     * @return array
     * @throws RestApiException
     * @throws MarketplaceException
     */
    protected function fetchShopeeOrders(Store $store)
    {
        $orderInputs = new Collection($this->orderInputs);
        $orderLists  = Service::shopee()->getOrderDetails($store, $orderInputs->pluck('order_sn')->all());

        return Collection::make($orderLists)
            ->map(function (array $order) use ($orderInputs) {
                // Do api shopee cache order_status nên nếu đã xác định được order_status thì overwrite
                $orderInput = $orderInputs->firstWhere('order_sn', $order['order_sn']);
                if (!empty($orderInput['order_status'])) {
                    $order['order_status'] = $orderInput['order_status'];
                }

                return $order;
            })
            ->where('order_status', '!=', Shopee::ORDER_STATUS_UNPAID)
            ->all();
    }

    /**
     * @param Store $store
     * @param $shippingCarrier
     * @return mixed
     * @throws MarketplaceException
     * @throws RestApiException
     */
    protected function getLogistic(Store $store, $shippingCarrier)
    {
        $logistic = $this->getShopeeLogistics($store)->firstWhere('logistics_channel_name', $shippingCarrier);
        if(empty($logistic) && !empty($shippingCarrier)) {
            //đoạn này do api shopee chỉ trả về "Nhanh", "Hỏa Tốc", "Tiết Kiệm"
            $logistic = Service::shopee()->makeLogisticDataFromName($shippingCarrier);
        }
        return $logistic;
    }

    /**
     * @param Store $store
     * @return Collection
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function getShopeeLogistics(Store $store)
    {
        if (!is_null($this->shopeeLogistics)) {
            return $this->shopeeLogistics;
        }

        $logisticLists = Service::shopee()->getLogistic($store);

        return $this->shopeeLogistics = new Collection($logisticLists);
    }
}
