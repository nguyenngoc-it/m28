<?php

namespace Modules\Product\Services;

class SkuEvent
{
    const SKU_CREATE              = 'SKU.CREATE';
    const SKU_COMBO_CREATE        = 'SKU_COMBO.CREATE';
    const SKU_COMBO_UPDATE        = 'SKU_COMBO.UPDATE';
    const SKU_COMBO_SKU_UPDATE    = 'SKU_COMBO_SKU.UPDATE';
    const SKU_COMBO_ADD_SKU       = 'SKU_COMBO.ADD_SKU';
    const SKU_COMBO_REMOVE_SKU    = 'SKU_COMBO.REMOVE_SKU';
    const SKU_COMBO_UPDATE_SKU    = 'SKU_COMBO.UPDATE_SKU';
    const STORE_SKU_UPDATE        = 'SKU.STORE_SKU_UPDATE';
    const STORE_SKU_DELETE        = 'SKU.STORE_SKU_DELETE';
    const SKU_UPDATE              = 'SKU.UPDATE';
    const SKU_UPDATE_STATUS       = 'SKU.UPDATE_STATUS';
    const SKU_UPDATE_PRICE        = 'SKU.UPDATE_PRICE';
    const SKU_UPDATE_REF          = 'SKU.UPDATE_REF';
    const SKU_UPDATE_SAFETY_STOCK = 'SKU.UPDATE_SAFETY_STOCK';
    const CHANGE_STOCK            = 'SKU.CHANGE_STOCK';

    const SKU_CREATE_BATCH_OF_GOODS = 'SKU.CREATE_BATCH_OF_GOOD'; // Tạo 1 lô hàng cho sku
    const SKU_IS_BATCH_UPDATE       = 'SKU.IS_BATCH_UPDATE'; // Cập nhật logic quản lý lô cho sku
}
