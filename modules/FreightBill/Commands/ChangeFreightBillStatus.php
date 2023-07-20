<?php

namespace Modules\FreightBill\Commands;

use App\Base\CommandBus;
use Modules\FreightBill\Models\FreightBill;
use Modules\FreightBill\Services\FreightBillEvent;
use Modules\OrderIntegration\PublicEvents\FreightBillUpdated;
use Modules\User\Models\User;

class ChangeFreightBillStatus extends CommandBus
{
    /**
     * @var FreightBill
     */
    public $freightBill;

    /**
     * @var string
     */
    public $status;

    /**
     * @var User
     */
    public $creator;

    /**
     * ChangeFreightBillStatus constructor.
     * @param FreightBill $freightBill
     * @param string $status
     * @param User $creator
     */
    public function __construct(FreightBill $freightBill, $status, User $creator)
    {
        $this->freightBill = $freightBill;
        $this->status = $status;
        $this->creator = $creator;
    }

    /**
     * @return FreightBill
     */
    public function handle()
    {
        if ($this->freightBill->status === $this->status) {
            return $this->freightBill;
        }

        $fromStatus = $this->freightBill->status;
        $this->freightBill->update(['status' => $this->status]);

        $this->freightBill->logActivity(FreightBillEvent::CHANGE_STATUS, $this->creator, [
            'old_status' => $fromStatus,
            'new_status' => $this->status,
        ]);

        return $this->freightBill;
    }
}
