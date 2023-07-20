<?php

namespace Modules\TikTokShop\Services;

class TikTokShop
{
    const WEBHOOK_ORDER_STATUS_UPDATED   = 1;
    const WEBHOOK_PRODUCT_STATUS_UPDATED = 5;

    const PRODUCT_STATUS_LIVE = 4;

    
    // https://developers.tiktok-shops.com/documents/document/234159
    const ORDER_STATUS_UNPAID              = 100;
    const ORDER_STATUS_AWAITING_SHIPMENT   = 111;
    const ORDER_STATUS_AWAITING_COLLECTION = 112;
    const ORDER_STATUS_PARTIALLY_SHIPPING  = 114;
    const ORDER_STATUS_IN_TRANSIT          = 121;
    const ORDER_STATUS_DELIVERED           = 122;
    const ORDER_STATUS_COMPLETED           = 130;
    const ORDER_STATUS_CANCELLED           = 140;

    // https://developers.tiktok-shops.com/documents/document/237446
    const LOGISTICS_TO_FULFILL = 1;
    const LOGISTICS_PROCESSING = 2;
    const LOGISTICS_FULFILLING = 3;
    const LOGISTICS_COMPLETED  = 4;
    const LOGISTICS_CANCELLED  = 5;
}
