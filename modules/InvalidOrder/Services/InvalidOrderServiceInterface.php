<?php

namespace Modules\InvalidOrder\Services;

use Modules\InvalidOrder\Models\InvalidOrder;
use Modules\Order\Models\Order;

interface InvalidOrderServiceInterface
{
    /**
     * Đồng bộ lại đơn lỗi
     *
     * @param InvalidOrder $invalidOrder
     * @return Order|null
     */
    public function resync(InvalidOrder $invalidOrder);

    /**
     * Xóa đơn lỗi tương ứng với đơn
     *
     * @param string $source
     * @param Order $order
     * @return InvalidOrder|null
     */
    public function remove($source, Order $order);
}
