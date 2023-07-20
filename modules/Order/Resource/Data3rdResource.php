<?php

namespace Modules\Order\Resource;


class Data3rdResource
{
    public $payment = [
        "payment_type" => '',
        "payment_time" => '',
        "payment_note" => '',
        "payment_method" => '',
        "payment_amount" => '',
        "bank_account" => '',
        "bank_name" => '',
        "standard_code" => ''
    ];

    /**
     * "receiver" => [
     * 'name'    => Tên người nhận
     * 'phone'   => Số DT người nhận
     * 'address' => Địa chỉ người nhận
     * ]
     *
     * @var array
     */
    public $receiver = [
        'name' => '',
        'phone' => '',
        'address' => '',
        'country_id' => 0,
        'province_id' => 0,
        'district_id' => 0,
        'ward_id' => 0,
        'postal_code' => '',
    ];

    /**
     * Source Create Data - Check From Model Modules\Marketplace\Services\Marketplace
     *
     * @var [string]
     */
    public $marketplace_code;
    /**
     * Id đơn hàng từ bên thứ 3
     *
     * @var string
     */
    public $id;
    /**
     * Mã đơn hàng
     *
     * @var string
     */
    public $code;
    /**
     * Mã ref code
     *
     * @var string
     */
    public $refCode;
    /**
     * Giá trị của đơn hàng
     *
     * @var float
     */
    public $order_amount;
    /**
     * Phí ship
     *
     * @var float
     */
    public $shipping_amount;
    /**
     * Giảm giá
     *
     * @var float
     */
    public $discount_amount;
    /**
     * Tổng tiền
     *
     * @var float
     */
    public $total_amount;
    /**
     * Currency Id
     *
     * @var int
     */
    public $currency_id;
    /**
     * Thời gian dự kiến giao hàng
     *
     * @var datetime
     */
    public $intended_delivery_at;
    /**
     * Mã vận đơn
     *
     * @var string
     */
    public $freight_bill;
    /**
     * Thời gian tạo đơn hàng từ bên thứ 3
     *
     * @var datetime
     */
    public $created_at_origin;
    /**
     * Sử dụng COD hay không
     *
     * @var boolean
     */
    public $using_cod;
    /**
     * Trạng thái đơn hàng - Check From Modules\Order\Models\Order
     *
     * @var string
     */
    public $status;
    /**
     * Waehouse Id
     *
     * @var int
     */
    public $warehouse_id;
    /**
     * Merchant Id
     *
     * @var int
     */
    public $merchant_id;

    /**
     * Creator Id
     *
     * @var int
     */
    public $creator_id;
    /**
     * Shipping Partner Id
     *
     * @var int
     */
    public $shipping_partner_id;
    /**
     * Description Order
     *
     * @var string
     */
    public $description;
    /**
     *  Campaign Order
     *
     * @var string
     */
    public $campaign;
    /**
     * Shipping Partner Data
     *
     * @var array
     */
    public $shipping_partner = [
        'id' => '',
        'code' => '',
        'name' => '',
        'provider' => '',
    ];
    /**
     * Danh sách Sku Item Của Đơn item
     * [
     * 'id_origin'       => Sku Id Origin From 3rd Partner
     * 'code'            => Sku Code,
     * 'discount_amount' => Order Sku Item Discount Amount,
     * 'price'           => Base Price Of Sku Item,
     * 'quantity'        => Quantity Of Sku Item,
     * ]
     *
     * @var array
     */
    public $items;

    /**
     * Danh sách Sku Item Combo Của Đơn item
     * [
     * 'id_origin'       => Sku Id Origin From 3rd Partner
     * 'code'            => Sku Code,
     * 'discount_amount' => Order Sku Item Discount Amount,
     * 'price'           => Base Price Of Sku Item,
     * 'quantity'        => Quantity Of Sku Item,
     * ]
     *
     * @var array
     */
    public $itemCombos;
}
