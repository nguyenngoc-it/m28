<?php

namespace Modules\Order\Resource;


class DataResource
{
    /**
     * "receiver" => [
                'name'        => Tên người nhận
                'phone'       => Số DT người nhận
                'address'     => Địa chỉ người nhận
                'province_id' => Id Province người nhận
                'district_id' => Id District người nhận
                'ward_id'     => Id Ward người nhận
            ]
     *
     * @var array
     */
    public $receiver = [
        'name'        => '',
        'phone'       => '',
        'address'     => '',
        'country_id'  => '',
        'province_id' => '',
        'district_id' => '',
        'ward_id'     => '',
    ];
    /**
     * Giá trị của đơn hàng
     *
     * @var float
     */
    public $order_amount;
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
     * Ghi chú đơn hàng
     *
     * @var string
     */
    public $description;
    /**
     * Danh sách Sku Item Của Đơn item
     * [
        'id'              => Sku Id
        'discount_amount' => Order Sku Item Discount Amount,
        'price'           => Base Price Of Sku Item,
        'quantity'        => Quantity Of Sku Item,
        ]
     *
     * @var array
     */
    public $items;

    /** Mã code địa chỉ người nhận
     * @var
     */
    public $receiverPostalCode;

    /**
     * Trạng thái đơn hàng - Check From Modules\Order\Models\Order
     *
     * @var string
     */
    public $status;

    /**
     * Lý do huỷ
     *
     * @var string
     */
    public $cancelReason;
}
