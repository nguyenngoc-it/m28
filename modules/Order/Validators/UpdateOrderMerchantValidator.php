<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;

class UpdateOrderMerchantValidator extends Validator
{
    /**
     * UpdateOrderValidator constructor.
     * @param Order $order
     * @param array $input
     */
    public function __construct(Order $order, array $input)
    {
        $this->order = $order;
        parent::__construct($input);
    }

    /**
     * @var Order
     */
    protected $order;


    /**
     * @return array
     */
    public function rules()
    {
        return [
            'receiver_name'        => 'required|string',
            'receiver_phone'       => 'required|string',
            'receiver_address'     => 'string',
            'receiver_country_id'  => 'integer',
            'receiver_province_id' => 'integer',
            'receiver_district_id' => 'integer',
            'receiver_ward_id'     => 'integer',
            'product_quantity'     => 'integer',
            'total_amount'         => 'required|numeric',
            'orderSkus'            => 'required|array',
        ];
    }

    protected function customValidate()
    {
        if (!$this->validateOrderStatus()) {
            $this->errors()->add('order_status', [self::ERROR_INVALID => "{$this->order->status} STATUS NOT ALLOW EDIT"]);
            return;
        }

        if (!$this->validateOrderMarketType()) {
            $this->errors()->add('order_marketplace_type', [self::ERROR_INVALID => "{$this->order->marketplace_code} MARKETPLACE NOT ALLOW EDIT"]);
            return;
        }

        if (!$this->validateOrderInspected()) {
            $this->errors()->add('order_inspected', [self::ERROR_INVALID => "ORDER INSPECTED NOT ALLOW EDIT"]);
            return;
        }

        if (!$this->validateOrderTotalAmount()) {
            $this->errors()->add('order_total_amount', [self::ERROR_INVALID => "ORDER TOTAL AMOUNT MUST >= 0"]);
            return;
        }
    }

    /**
     * Validate trạng thái đơn hàng có cho phép sửa hay không
     *
     * @return boolean
     */
    protected function validateOrderStatus()
    {
        $orderStatusValidList = [
            Order::STATUS_WAITING_PROCESSING,
            Order::STATUS_WAITING_PICKING
        ];

        $check = in_array($this->order->status, $orderStatusValidList);
        return $check;
    }

    /**
     * Validate kiểu đơn hàng
     *
     * @return boolean
     */
    protected function validateOrderMarketType()
    {
        $orderMarketTypeValidList = [
            Marketplace::CODE_MANUAL
        ];

        $check = in_array($this->order->marketplace_code, $orderMarketTypeValidList);
        return $check;
    }

    /**
     * Validate đơn hàng đã được chọn vị trí hay chưa
     *
     * @return boolean
     */
    protected function validateOrderInspected()
    {
        // Check thông tin sảnn phẩm của đơn có bị sửa hay không
        $diff = true;
        if ($this->order->inspected) {
            $orderSkus      = $this->order->skus->pluck(['id'])->all();
            $orderSkusInput = collect(data_get($this->input, 'orderSkus', []))->pluck(['id'])->all();

            $diff = array_diff($orderSkusInput, $orderSkus) === array_diff($orderSkus, $orderSkusInput);

        }
        return $diff;
    }

    /**
     * Validate thông tin tổng thanh toán của đơn
     *
     * @return boolean
     */
    protected function validateOrderTotalAmount()
    {
        $totalAmount = data_get($this->input, 'total_amount', 0);
        if ($totalAmount < 0) {
            return false;
        } else {
            return true;
        }
    }
}
