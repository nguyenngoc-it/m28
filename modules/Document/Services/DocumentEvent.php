<?php

namespace Modules\Document\Services;

class DocumentEvent
{
    const CREATE = 'DOCUMENT.CREATE';
    const UPDATE = 'DOCUMENT.UPDATE';
    const CANCEL = 'DOCUMENT.CANCEL';
    const EXPORT = 'DOCUMENT.EXPORT';
    const IMPORT = 'DOCUMENT.IMPORT';
    const CONFIRM = 'DOCUMENT.CONFIRM';

    const INVENTORY                     = 'DOCUMENT.INVENTORY'; // Xác nhận Kiểm kê
    const BALANCE_INVENTORY             = 'DOCUMENT.BALANCE_INVENTORY'; // Cân bằng Kiểm kê
    const COMPLETE_INVENTORY            = 'DOCUMENT.COMPLETE_INVENTORY'; // Xác nhận kết thúc Kiểm kê
    const SCAN_INVENTORY                = 'DOCUMENT.SCAN_INVENTORY'; // Quét kiểm kê
    const UPDATE_QUANTITY_SKU_INVENTORY = 'DOCUMENT.UPDATE_QUANTITY_SKU_INVENTORY'; // Cập nhật số lượng sản phẩm kiểm kê
}
