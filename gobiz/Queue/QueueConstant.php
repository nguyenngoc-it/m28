<?php

namespace Gobiz\Queue;

class QueueConstant
{
    /**
     * name queues
     */
    const EVENT_QUEUES = 'event_queues';

    const MODULE_ORDER_LOG              = 'order_log';
    const MODULE_ORDER_FINALI_LOG       = 'order_finali_log';
    const MODULE_USER_LOG               = 'user_log';
    const MODULE_COMPLAINT_SELLER_LOG   = 'complaint_seller_log';
    const MODULE_PRODUCT_RETURN_LOG     = 'product_return_log';
    const MODULE_TRANSACTION_LOG        = 'transaction_log';
    const MODULE_PAYMENT_REQUEST_LOG    = 'payment_request_log';
}