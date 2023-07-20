<?php

namespace Modules\FreightBill\Services;

use Modules\FreightBill\Models\FreightBill;
use Modules\User\Models\User;

interface FreightBillServiceInterface
{
    /**
     * Thay đổi trạng thái mã vận đơn
     *
     * @param FreightBill $freightBill
     * @param string $status
     * @param User $creator
     * @return FreightBill
     */
    public function changeStatus(FreightBill $freightBill, $status, User $creator);
}
