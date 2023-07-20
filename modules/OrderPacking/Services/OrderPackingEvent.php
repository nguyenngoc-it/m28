<?php

namespace Modules\OrderPacking\Services;

class OrderPackingEvent
{
    const CREATE                     = 'ORDER_PACKING.CREATE';
    const UPDATE_SERVICE             = 'ORDER_PACKING.UPDATE_SERVICE';
    const CHANGE_SHIPPING_PARTNER    = 'ORDER_PACKING.CHANGE_SHIPPING_PARTNER';
    const CREATE_FREIGHT_BILL        = 'ORDER_PACKING.CREATE_FREIGHT_BILL';
    const UPDATE_FREIGHT_BILL_MANUAL = 'ORDER_PACKING.UPDATE_FREIGHT_BILL_MANUAL'; // Cập nhật lại mã vận đơn bằng tay hoặc tools
    const CANCEL_FREIGHT_BILL        = 'ORDER_PACKING.CANCEL_FREIGHT_BILL';
    const CHANGE_STATUS              = 'ORDER_PACKING.CHANGE_STATUS';
    const GRANT_PICKER               = 'ORDER_PACKING.GRANT_PICKER';
}
