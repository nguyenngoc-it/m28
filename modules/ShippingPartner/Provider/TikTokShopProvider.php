<?php

namespace Modules\ShippingPartner\Provider;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiInterface;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Psr\Log\LoggerInterface;

class TikTokShopProvider implements ShippingPartnerApiInterface
{
    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * ShopeeProvider constructor.
     */
    public function __construct()
    {
        $this->logger = LogService::logger('shopee-provider');
    }

    /**
     * @param OrderPacking $orderPacking
     * @param null $pickupType
     * @return ShippingPartnerOrder|void
     * @throws ShippingPartnerApiException
     */
    public function createOrder(OrderPacking $orderPacking, $pickupType = null)
    {
        // TODO: Implement mappingOrder() method.
    }

    /**
     * Lấy url in tem của danh sách mã vận đơn
     * @param int $shippingPartnerId
     * @param array $freightBillCodes
     * @return array|string
     * @throws RestApiException
     */
    public function getOrderStampsUrl($shippingPartnerId, array $freightBillCodes)
    {
        return Service::tikTokShop()->downloadShippingDocument($shippingPartnerId, $freightBillCodes);
    }

    /**
     * Đồng bộ vận đơn sang M32
     *
     * @param OrderPacking $orderPacking
     * @return void
     */
    public function mappingOrder(OrderPacking $orderPacking)
    {
        // TODO: Implement mappingOrder() method.
    }
}
