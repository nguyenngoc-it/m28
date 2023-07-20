<?php

namespace Modules\Tiki\Services;

class Tiki
{
    const WEBHOOK_ORDER_STATUS_UPDATED = 'ORDER_STATUS_UPDATED';
    const WEBHOOK_ORDER_STATUS_CREATED = 'ORDER_CREATED_SUCCESSFULLY';

    // https://open.tiki.vn/docs/docs/current/guides/tiki-theory-v2/order-status-v2/
    const ORDER_STATUS_WAITING_INSPECTION  = 'queueing';
    const ORDER_STATUS_CANCELED            = 'canceled';
    const ORDER_STATUS_COMPLETE            = 'complete';
    const ORDER_STATUS_DELIVERY_SUCCESS    = 'successful_delivery';
    const ORDER_STATUS_PROCESSING          = 'processing';
    const ORDER_STATUS_WAITING_PAYMENT     = 'waiting_payment';
    const ORDER_STATUS_HANDOVER_TO_PARTNER = 'handover_to_partner';
    const ORDER_STATUS_CLOSED              = 'closed';
    const ORDER_STATUS_PACKAGING           = 'packaging';
    const ORDER_STATUS_PICKING             = 'picking';
    const ORDER_STATUS_SHIPPING            = 'shipping';
    const ORDER_STATUS_PAID                = 'paid';
    const ORDER_STATUS_DELIVERD            = 'delivered';
    const ORDER_STATUS_HOLDED              = 'holded';
    const ORDER_STATUS_READY_TO_SHIP       = 'ready_to_ship';
    const ORDER_STATUS_PAYMENT_REVIEW      = 'payment_review';
    const ORDER_STATUS_RETURNED            = 'returned';
    const ORDER_STATUS_FINISHED_PACKING    = 'finished_packing';

    const LOGISTICS_NOT_START               = 1; //chưa giao hàng
    const LOGISTICS_STARTING                = 2; //đang giao hàng
    const LOGISTICS_DELIVERY_DONE           = 3; //đã giao hàng
    const LOGISTICS_DELIVERY_RETURN         = 4; //đang chuyển hoàn
    const LOGISTICS_DELIVERY_RETURNED       = 5; //đã chuyển hoàn
    const LOGISTICS_DELIVERY_CANCELED       = 6; //đã huỷ
    const LOGISTICS_PICKUP_STARTING         = 7; //đang lấy hàng
    const LOGISTICS_PICKUP_RESTART          = 8; //chờ lấy lại
    const LOGISTICS_PICKUP_DONE             = 9; //đã lấy hàng
    const LOGISTICS_DELIVERY_RESTART        = 10; //chờ giao lại
    const LOGISTICS_DELIVERY_WAIT_RETURN    = 11; //chờ chuyển hoàn
    const LOGISTICS_DELIVERY_RETURN_RESTART = 12; //chờ chuyển hoàn lại
}
