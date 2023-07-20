<?php

namespace Modules\Sapo\Services;

class Sapo
{
    const WEBHOOK_ORDER_STATUS_UPDATED       = 'orders';
    const WEBHOOK_PRODUCT_STATUS_UPDATED     = 'products';
    const WEBHOOK_FULFILLMENT_STATUS_UPDATED = 'fulfillments';

    const PRODUCT_STATUS_LIVE = 4;

    
    // https://api-doc.shopbase.com/#tag/Order/operation/retrieves-a-specific-order
    const ORDER_STATUS_PENDING    = 'pending';
    const ORDER_STATUS_OPEN       = 'open';
    const ORDER_STATUS_SUCCESS    = 'success';
    const ORDER_STATUS_CANCELLED  = 'cancelled';
    const ORDER_STATUS_ERROR      = 'error';
    const ORDER_STATUS_FAILURE    = 'failure';
    const ORDER_STATUS_PROCESSING = 'processing';

    // https://support.sapo.vn/fulfillment#index
    const LOGISTICS_ATTEMPTED_DELIVERY = 'attempted_delivery';
    const LOGISTICS_READY_TO_PICKUP    = 'ready_for_pickup';
    const LOGISTICS_CONFIRMED          = 'confirmed';
    const LOGISTICS_IN_TRANSIT         = 'in_transit';
    const LOGISTICS_OUT_FOR_DELIVERY   = 'out_for_delivery';
    const LOGISTICS_DELIVERED          = 'delivered';
    const LOGISTICS_DELAYED            = 'delayed';
    const LOGISTICS_FAILURE            = 'failure';
    const LOGISTICS_NOT_FOUND          = 'not_found';
    const LOGISTICS_INVALID            = 'invalid';
}
