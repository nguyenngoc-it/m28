<?php

namespace Modules\Product\Services;

class ProductEvent
{
    const CREATE        = 'PRODUCT.CREATE';
    const UPDATE        = 'PRODUCT.UPDATE';
    const UPDATE_STATUS = 'PRODUCT.UPDATE_STATUS';

    const SKU_UPDATE_STATUS              = 'PRODUCT.SKU_UPDATE_STATUS';
    const SKU_UPDATE_PRICE               = 'PRODUCT.SKU_UPDATE_PRICE';
    const SKU_CREATE                     = 'PRODUCT.SKU_CREATE';
    const SKU_DELETE                     = 'PRODUCT.SKU_DELETE';
    const SKU_UPDATE_REF                 = 'PRODUCT.SKU_UPDATE_REF';
    const SKU_UPDATE                     = 'PRODUCT.SKU_UPDATE';
    const UPDATE_PRODUCT_PRICE_STATUS    = 'PRODUCT.UPDATE_PRODUCT_PRICE_STATUS';
    const CONFIRM_WEIGHT_VOLUME_FOR_SKUS = 'PRODUCT.CONFIRM_WEIGHT_VOLUME_FOR_SKUS';

    const UPDATE_SERVICE          = 'PRODUCT.UPDATE_SERVICE';
    const REMOVE_SERVICE          = 'PRODUCT.REMOVE_SERVICE';
    const SKU_UPDATE_SAFETY_STOCK = 'PRODUCT.SKU_UPDATE_SAFETY_STOCK';
}
