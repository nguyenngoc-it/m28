<?php

namespace Modules\PurchasingOrder\Commands;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Services\Permission;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\User\Models\User;

class CheckPemissionView
{
    /** @var PurchasingOrder */
    protected $purchasingOrder;
    /** @var User */
    protected $user;

    /**
     * CheckPemissionView constructor.
     * @param PurchasingOrder $purchasingOrder
     * @param User $user
     */
    public function __construct(PurchasingOrder $purchasingOrder, User $user)
    {
        $this->purchasingOrder = $purchasingOrder;
        $this->user            = $user;
    }

    /**
     * @return array
     */
    public function handle()
    {
        $pemissionViews                                               = [
            PurchasingOrder::PERMISSION_VIEW_MAPPING_SKU => false
        ];
        $pemissionViews[PurchasingOrder::PERMISSION_VIEW_MAPPING_SKU] = $this->checkMappingSkus();

        return $pemissionViews;
    }

    /**
     * @return boolean
     */
    protected function checkMappingSkus()
    {
        if ($this->user->can(Permission::MERCHANT_SKU_MAP_ALL)) {
            return true;
        } else if ($this->user->can(Permission::MERCHANT_SKU_MAP_ASSIGNED)) {
            if (in_array($this->purchasingOrder->merchant_id, $this->user->merchants->pluck('id')->all())) {
                return true;
            }
        }

        return false;
    }
}
