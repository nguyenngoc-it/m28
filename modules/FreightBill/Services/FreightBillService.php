<?php

namespace Modules\FreightBill\Services;

use Modules\FreightBill\Commands\ChangeFreightBillStatus;
use Modules\FreightBill\Commands\ChangeFreightBillStatusNew;
use Modules\FreightBill\Models\FreightBill;
use Modules\User\Models\User;

class FreightBillService implements FreightBillServiceInterface
{
    /**
     * Thay đổi trạng thái mã vận đơn
     *
     * @param FreightBill $freightBill
     * @param string $status
     * @param User $creator
     * @return FreightBill
     */
    public function changeStatus(FreightBill $freightBill, $status, User $creator)
    {
        return (new ChangeFreightBillStatus($freightBill, $status, $creator))->dispatch();
    }
}
