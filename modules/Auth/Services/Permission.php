<?php

namespace Modules\Auth\Services;

class Permission
{
    const INTERNAL_API_CALL = 'INTERNAL_API:CALL';
    const INTERNAL_FIX_DATA = 'INTERNAL_FIX_DATA'; // Quyền cho phép sửa đổi dữ liệu
    const EXTERNAL_API_CALL = 'EXTERNAL_API:CALL';
    const SYSTEM_DATA_OPS   = 'SYSTEM:DATA_OPS'; // Quyền liên quan đến quản lý data hệ thống (sync data, ...)

    const MERCHANT_VIEW                      = 'MERCHANT:VIEW';
    const MERCHANT_CREATE                    = 'MERCHANT:CREATE';
    const MERCHANT_UPDATE                    = 'MERCHANT:UPDATE';
    const MERCHANT_CONNECT_SHOP_BASE         = 'MERCHANT:CONNECT_SHOP_BASE';
    const MERCHANT_CONNECT_PURCHASING        = 'MERCHANT:CONNECT_PURCHASING'; // quản lý tài khoản mua hàng
    const MERCHANT_PURCHASING_ORDER_ALL      = 'MERCHANT:PURCHASING_ORDER:ALL'; // Xem toàn bộ đơn hàng nhập
    const MERCHANT_PURCHASING_ORDER_ASSIGNED = 'MERCHANT:PURCHASING_ORDER:ASSIGNED'; // Xem hàng nhập của vendor mà user đang quản lý
    const MERCHANT_SKU_MAP_ALL               = 'MERCHANT:SKU_MAP:ALL'; // Quyền map sku cho toàn bộ sản phẩm
    const MERCHANT_SKU_MAP_ASSIGNED          = 'MERCHANT:SKU_MAP:ASSIGNED'; // Quyền map sku cho sp của đơn thuộc merchant mà user đang quản lý
    const MERCHANT_MANAGE_STORE              = 'MERCHANT:MANAGE_STORE'; // Quản lý store

    const FINANCE_VIEW_INBOUND_SHIPMENT                                = 'FINANCE:VIEW_INBOUND_SHIPMENT'; // quyền tài chính
    const FINANCE_VIEW_SELLER_REPORT                                   = 'FINANCE:VIEW_SELLER_REPORT'; // xem danh sách tài chính đơn với seller
    const FINANCE_CREATE_STATEMENT                                     = 'FINANCE:CREATE_STATEMENT'; // Tạo chứng từ đối soát COD
    const FINANCE_VIEW_STATEMENT                                       = 'FINANCE:VIEW_STATEMENT'; // Xem chứng từ đối soát vận đơn
    const FINANCE_CONFIRM_STATEMENT                                    = 'FINANCE:CONFIRM_STATEMENT'; // Xác nhận chứng từ đối soát COD
    const FINANCE_VIEW_SELLER_WALLET                                   = 'FINANCE:VIEW_SELLER_WALLET'; // để xem được số dư và chi tiết giao dịch của từng seller
    const FINANCE_EDIT_SELLER_WALLET                                   = 'FINANCE:EDIT_SELLER_WALLET'; //Nạp và rút tiền ví seller
    const FINANCE_CREATE_DELIVERY_STATEMENT                            = 'FINANCE:CREATE_DELIVERY_STATEMENT'; // tạo chứng từ đối soát giao nhận vận chuyển
    const FINANCE_VIEW_DELIVERY_STATEMENT                              = 'FINANCE:VIEW_DELIVERY_STATEMENT'; // xem chứng từ đối soát giao nhận vận chuyển
    const FINANCE_SHIPPING_PARTNER_EXPECTED_TRANSPORTING_PRICES_CONFIG = 'FINANCE:SHIPPING_PARTNER_EXPECTED_TRANSPORTING_PRICES_CONFIG'; // Cấu hình bảng giá vận chuyển

    const WAREHOUSE_VIEW           = 'WAREHOUSE:VIEW';
    const WAREHOUSE_CREATE         = 'WAREHOUSE:CREATE';
    const WAREHOUSE_UPDATE         = 'WAREHOUSE:UPDATE';
    const WAREHOUSE_IMPORT_HISTORY = 'WAREHOUSE:IMPORT_HISTORY';
    const WAREHOUSE_CREATE_AREA    = 'WAREHOUSE:CREATE_AREA'; // tạo và cập nhật vị trí kho

    const STOCK_VIEW        = 'STOCK:VIEW';
    const OPERATION_ARRANGE = 'OPERATION:ARRANGE'; // Chuyển tồn kho sang vị trí mới

    const ORDER_VIEW_LIST               = 'ORDER:VIEW_LIST';
    const ORDER_CREATE                  = 'ORDER:CREATE';
    const ORDER_UPDATE                  = 'ORDER:UPDATE';
    const ORDER_VIEW_DETAIL             = 'ORDER:VIEW_DETAIL';
    const ORDER_PACKAGED                = 'ORDER:PACKAGED';
    const ORDER_IMPORT_STATUS           = 'ORDER:IMPORT_STATUS';
    const ORDER_IMPORT_FREIGHT_BILL     = 'ORDER:IMPORT_FREIGHT_BILL';
    const ORDER_VIEW_FAILED_ORDER       = 'ORDER:VIEW_FAILED_ORDER';
    const ORDER_REMOVE_FAILED_ORDER     = 'ORDER:REMOVE_FAILED_ORDER';
    const ORDER_CANCEL_FREIGHT_BILL     = 'ORDER:CANCEL_FREIGHT_BILL';
    const ORDER_CHANGE_FINANCIAL_STATUS = 'ORDER:CHANGE_FINANCIAL_STATUS';
    const ADMIN_PACKAGE_CREATE = 'ADMIN:PACKAGE_CREATE';


    const OPERATION_PREPARATION         = 'OPERATION:PREPARATION'; // Quyền tạo chứng từ đóng hàng và xem ds ycdh/chi tiết chứng từ đóng hàng do mình xác nhận
    const OPERATION_HISTORY_PREPARATION = 'OPERATION:HISTORY:PREPARATION'; // Quyền xem chi tất cả tiết chứng từ đóng hàng
    const OPERATION_EXPORT              = 'OPERATION:EXPORT'; // Quyền thao tác với chứng từ xuất hàng do mình xác nhận
    const OPERATION_HISTORY_EXPORT      = 'OPERATION:HISTORY:EXPORT'; // Quyền xem tất cả chứng từ xuất hàng

    const OPERATION_HISTORY_AUDIT_EDIT    = 'OPERATION:HISTORY:AUDIT:EDIT'; // Quyền thực hiện kiểm kê kho
    const OPERATION_HISTORY_AUDIT_VIEW    = 'OPERATION:HISTORY:AUDIT:VIEW'; // Quyền xem kiểm kê kho
    const OPERATION_HISTORY_AUDIT_CONFIRM = 'OPERATION:HISTORY:AUDIT:CONFIRM'; // Quyền hoàn tất kiểm kê kho

    const OPERATION_SCAN_AFTER_PACKAGED = 'OPERATION:SCAN_AFTER_PACKAGED'; // Quyền quét xác nhận sau khi đóng hàng
    const OPERATION_IMPORT              = 'OPERATION:IMPORT'; // Quyền thao tác với chứng từ nhập hàng
    const OPERATION_HISTORY_IMPORT      = 'OPERATION:HISTORY:IMPORT'; // Quyền xem tất cả chứng từ nhập hàng

    const PRODUCT_VIEW_LIST   = 'PRODUCT:VIEW_LIST'; // View list product và sku
    const PRODUCT_MANAGE_ALL  = 'PRODUCT:MANAGE_ALL'; // Xem/ sửa tất cả sản phẩm mà không phụ thuộc vào seller
    const PRODUCT_VIEW_DETAIL = 'PRODUCT:VIEW_DETAIL'; // View detail product và sku
    const PRODUCT_CREATE      = 'PRODUCT:CREATE'; // Import, create product và sku
    const PRODUCT_UPDATE      = 'PRODUCT:UPDATE'; // Update product và sku

    const SKU_CONFIG_EXTERNAL_CODE    = 'SKU:CONFIG_EXTERNAL_CODE'; // Import, update, delete external code sku
    const SKU_VIEW_LIST_EXTERNAL_CODE = 'SKU:VIEW_LIST_EXTERNAL_CODE'; // View list external code sku
    const SKU_UPDATE                  = 'SKU:UPDATE'; // Cập nhật SKU

    const USER_MERCHANT_VIEW   = 'USER_MERCHANT:VIEW';
    const USER_MERCHANT_ADD    = 'USER_MERCHANT:ADD';
    const USER_MERCHANT_UPDATE = 'USER_MERCHANT:UPDATE';


    const DELIVERY_NOTE_CREATE = 'DELIVERY_NOTE:CREATE';
    const DELIVERY_NOTE_VIEW   = 'DELIVERY_NOTE:VIEW';

    const CONFIG_CATEGORIES_UPDATE = "CONFIG:CATEGORIES_UPDATE";
    const CONFIG_CATEGORIES_VIEW   = "CONFIG:CATEGORIES_VIEW";

    const  ORDER_VIEW_CUSTOMER  = "ORDER:VIEW_CUSTOMER"; //ẩn thông tin khách hàng
    const  ORDER_UPDATE_CARRIER = "ORDER:UPDATE_CARRIER"; //Đổi đơn vị vận chuyển
    const  ORDER_PRINT_BILL     = "ORDER:PRINT_BILL"; //in tem

    const SERVICE_VIEW = 'SERVICE:VIEW'; // Xem danh sách dịch vụ
    const SERVICE_ADD  = 'SERVICE:ADD'; // Thêm dịch vụ
    const SERVICE_STOP = 'SERVICE:STOP'; // Dừng dịch vụ

    /**
     * Policy permissions
     *  - Permission chỉ dùng trong policy chứ không dùng để gán trực tiếp cho user
     *  - Permission phải theo format "action-name"
     */
    const PRODUCT_MANAGE   = 'product-manage'; // Quyền quản lý sản phẩm phụ thuộc vào seller mà user quản lý
    const QUOTATION_CREATE = "QUOTATION:CREATE"; //quyền tạo báo giá sản phẩm
    const QUOTATION_CANCEL = "QUOTATION:CANCEL"; //quyền hủy sản phẩm

    const ADMIN_SET_ORDER_FLOW = "ADMIN:SET_ORDER_FLOW"; //Quyền cấu hình tự động xác nhận đơn và tạo vận đơn.
    const ADMIN_SYSTEM_CONFIG = "ADMIN:SYSTEM_CONFIG"; //Quyền cấu hình bật tắt các cấu hình

    const ADMIN_CREATE_SUPPLIER = "ADMIN:CREATE_SUPPLIER";
    const ADMIN_UPDATE_SUPPLIER = "ADMIN:UPDATE_SUPPLIER";
    const OPERATION_VIEW_SUPPLIER = "OPERATION:VIEW_SUPPLIER";
    const ADMIN_ASSIGN_SUPPLIER    = 'ADMIN:ASSIGN_SUPPLIER';
    const OPERATION_VIEW_ALL_PRODUCT    = 'OPERATION:VIEW_ALL_PRODUCT';
    const FINANCE_CREATE_SUPPLIER_TRANSACTION    = 'FINANCE:CREATE_SUPPLIER_TRANSACTION';
}
