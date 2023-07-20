<?php

namespace Modules\Warehouse\Events;

use App\Base\Event;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class WarehouseCreated extends Event
{
    /** @var Warehouse $warehouse */
    public $warehouse;
    /** @var User $creator */
    public $creator;

    /**
     * OrderCreated constructor
     *
     * @param Warehouse $warehouse
     * @param User $creator
     */
    public function __construct(Warehouse $warehouse, User $creator)
    {
        $this->warehouse = $warehouse;
        $this->creator   = $creator;
    }
}
