<?php

namespace Modules\Lazada\Services;

class Lazada
{
    const WEBHOOK_ORDER_STATUS_UPDATE = 0;
    const WEBHOOK_ORDER_STATUS_REVERSE = 10;
    const WEBHOOK_PRODUCT_STATUS_CREATED = 3;
    const WEBHOOK_PRODUCT_STATUS_UPDATED = 4;

    const WEBHOOK_REVERSE_STATUS_CANCEL_INIT    = 'CANCEL_INIT';
    const WEBHOOK_REVERSE_STATUS_CANCEL_SUCCESS = 'CANCEL_SUCCESS';
    const WEBHOOK_REVERSE_STATUS_CANCEL_REFUND  = 'CANCEL_REFUND_ISSUED';

    const ORDER_STATUS_UNPAID                = 'unpaid'; 
    const ORDER_STATUS_PENDING               = 'pending'; 
    const ORDER_STATUS_PACKED                = 'packed';
    const ORDER_STATUS_READY_TO_SHIP_PENDING = 'ready_to_ship_pending'; 
    const ORDER_STATUS_READY_TO_SHIP         = 'ready_to_ship';
    const ORDER_STATUS_SHIPPED               = 'shipped'; 
    const ORDER_STATUS_DELIVERED             = 'delivered'; 
    const ORDER_STATUS_FAILED_DELIVERY       = 'failed_delivery'; 
    const ORDER_STATUS_LOST_BY_3PL           = 'lost_by_3pl'; 
    const ORDER_STATUS_DAMAGED_BY_3PL        = 'damaged_by_3pl';
    const ORDER_STATUS_RETURNED              = 'returned';
    const ORDER_STATUS_CANCELED              = 'canceled';
    const ORDER_STATUS_SHIPPED_BACK          = 'shipped_back';
    const ORDER_STATUS_SHIPPED_BACK_SUCCESS  = 'shipped_back_success';
    const ORDER_STATUS_SHIPPED_BACK_FAILED   = 'shipped_back_failed';

    const LOGISTICS_NOT_START        = 'LOGISTICS_NOT_START';
    const LOGISTICS_REQUEST_CREATED  = 'LOGISTICS_REQUEST_CREATED';
    const LOGISTICS_PICKUP_DONE      = 'LOGISTICS_PICKUP_DONE';
    const LOGISTICS_PICKUP_RETRY     = 'LOGISTICS_PICKUP_RETRY';
    const LOGISTICS_PICKUP_FAILED    = 'LOGISTICS_PICKUP_FAILED';
    const LOGISTICS_DELIVERY_DONE    = 'LOGISTICS_DELIVERY_DONE';
    const LOGISTICS_DELIVERY_FAILED  = 'LOGISTICS_DELIVERY_FAILED';
    const LOGISTICS_REQUEST_CANCELED = 'LOGISTICS_REQUEST_CANCELED';
    const LOGISTICS_COD_REJECTED     = 'LOGISTICS_COD_REJECTED';
    const LOGISTICS_READY            = 'LOGISTICS_READY';
    const LOGISTICS_INVALID          = 'LOGISTICS_INVALID';
    const LOGISTICS_LOST             = 'LOGISTICS_LOST';
    const LOGISTICS_UNKNOWN          = 'LOGISTICS_UNKNOWN';

    const ERROR_ACCESS_TOKEN_INVALID = 'ACCESS_TOKEN_INVALID';
    const ERROR_REFRESH_TOKEN_INVALID = 'REFRESH_TOKEN_INVALID';
}
