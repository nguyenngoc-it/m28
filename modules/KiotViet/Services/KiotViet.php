<?php

namespace Modules\KiotViet\Services;

class KiotViet
{
    const WEBHOOK_PRODUCT_STATUS_UPDATE = 'product.update';
    const WEBHOOK_ORDER_STATUS_UPDATE   = 'order.update';
    const WEBHOOK_INVOICE_STATUS_UPDATE = 'invoice.update';

    const ORDER_STATUS_WAITING_INSPECTION = 1; // Phiếu tạm - chờ chọn kho
    const ORDER_STATUS_DELIVERING         = 2; // Đang giao hàng
    const ORDER_STATUS_FINISH             = 3; // Hoàn thành
    const ORDER_STATUS_CANCELED           = 4; // Đã huỷ
    const ORDER_STATUS_WAITING_CONFIRM    = 5; // Đã xác nhận - Chờ xử lý

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
