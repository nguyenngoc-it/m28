<?php

namespace Modules\Order\Services;

class OrderEvent
{
    const CREATE                                = 'ORDER.CREATE';
    const UPDATE                                = 'ORDER.UPDATE';
    const CHANGE_STATUS                         = 'ORDER.CHANGE_STATUS';
    const INSPECTION                            = 'ORDER.INSPECTION';
    const CREATE_PACKAGE                        = 'ORDER.CREATE_PACKAGE';
    const PAYMENT_CONFIRM                       = 'ORDER.PAYMENT_CONFIRM';
    const REMOVE_IMPORTING_RETURN_GOODS_SERVICE = 'ORDER.REMOVE_IMPORTING_RETURN_GOODS_SERVICE';
    const ADD_IMPORTING_RETURN_GOODS_SERVICE    = 'ORDER.ADD_IMPORTING_RETURN_GOODS_SERVICE';
    const REMOVE_WAREHOUSE_AREA                 = 'ORDER.REMOVE_WAREHOUSE_AREA';
    const ADD_WAREHOUSE_AREA                    = 'ORDER.ADD_WAREHOUSE_AREA';
    const ADD_PRIORITY                          = 'ORDER.ADD_PRIORITY';

    const CANCEL                             = 'ORDER.CANCEL';
    const CHANGE_FREIGHT_BILL                = 'ORDER.CHANGE_FREIGHT_BILL';
    const CHANGE_PACKAGE_STATUS              = 'ORDER.CHANGE_PACKAGE_STATUS';
    const ADD_SKUS                           = 'ORDER.ADD_SKUS';
    const UPDATE_SKUS                        = 'ORDER.UPDATE_SKUS';
    const REMOVE_SKUS                        = 'ORDER.REMOVE_SKUS';
    const UPDATE_ATTRIBUTES                  = 'ORDER.UPDATE_ATTRIBUTES';
    const CHANGE_SHIPPING_PARTNER            = 'ORDER.CHANGE_SHIPPING_PARTNER';
    const UPDATE_COD                         = 'ORDER.UPDATE_COD';
    const UPDATE_FINANCE_STATUS              = 'ORDER.UPDATE_FINANCE_STATUS';
    const UPDATE_EXPECTED_TRANSPORTING_PRICE = 'ORDER.UPDATE_EXPECTED_TRANSPORTING_PRICE';

    const UPDATE_BATCH   = 'ORDER.UPDATE_BATCH'; // Cập nhật chọn 1 sku lô cha trên đơn
    const COMPLETE_BATCH = 'ORDER.COMPLETE_BATCH'; // Cập nhật chọn sku lô cho toàn bộ sku của đơn

    const CHANGE_AMOUNT                       = 'ORDER.CHANGE_AMOUNT';
}
