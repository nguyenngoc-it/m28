<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\User\Models\User;

class ImportedForConfirmValidator extends Validator
{
    /** @var Order */
    protected $order;
    /** @var array */
    protected $syncSkus;
    /** @var Location|null */
    protected $locationDistrict, $locationWard;

    public function __construct(User $user, array $row)
    {
        parent::__construct($row, $user);
        $this->user = $user;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'order_code' => 'required',
        ];
    }

    /**
     * @return Location|null
     */
    public function getLocationDistrict(): ?Location
    {
        return $this->locationDistrict;
    }

    /**
     * @return Location|null
     */
    public function getLocationWard(): ?Location
    {
        return $this->locationWard;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order->refresh();
    }

    /**
     * @return array
     */
    public function getSyncSkus(): array
    {
        return $this->syncSkus;
    }

    protected function customValidate()
    {
        $orderCode = $this->input('order_code');
        /** @var Order|null $order */
        $this->order = Order::query()->where([
            'code' => $orderCode,
            'tenant_id' => $this->user->tenant_id,
        ])->first();

        if (!$this->order) {
            $this->errors()->add('order', static::ERROR_EXISTS);
            return;
        }
        if (!in_array($this->order->merchant_id, $this->user->merchants->pluck('id')->all())) {
            $this->errors()->add('order', 'not_to_access_order');
            return;
        }
        if ($this->order && !in_array($this->order->status, [Order::STATUS_WAITING_INSPECTION, Order::STATUS_WAITING_CONFIRM])) {
            $this->errors()->add('order', static::ERROR_STATUS_INVALID);
            return;
        }
        /**
         * Không xác nhận nếu đơn chưa chọn dvvc
         */
        if (!$this->order->shipping_partner_id) {
            $this->errors()->add('order', 'shipping_partner_not_found');
            return;
        }
    }
}
