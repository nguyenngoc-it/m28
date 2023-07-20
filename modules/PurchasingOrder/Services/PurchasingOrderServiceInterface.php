<?php

namespace Modules\PurchasingOrder\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\Product\Models\Sku;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingVariant;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\User\Models\User;

interface PurchasingOrderServiceInterface
{
    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user);

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * Quyền hiển thị các actions trên giao diện
     *
     * @param PurchasingOrder $purchasingOrder
     * @param User $user
     * @return array
     */
    public function pemissionViews(PurchasingOrder $purchasingOrder, User $user);

    /**
     * @param PurchasingOrder $purchasingOrder
     * @param $status
     * @param User $user
     * @return mixed
     */
    public function changeState(PurchasingOrder $purchasingOrder, $status, User $user);

    /**
     * @param PurchasingOrder $purchasingOrder
     * @param array $input
     * @param User $user
     * @return mixed
     */
    public function updateMerchantPurchasingOrder(PurchasingOrder $purchasingOrder, array $input, User $user);

    /**
     * Map variant của 1 purchasing Order
     *
     * @param PurchasingOrder $purchasingOrder
     * @param PurchasingVariant $purchasingVariant
     * @param Sku $sku
     * @param User $user
     * @return void
     */
    public function mappingVariant(PurchasingOrder $purchasingOrder, PurchasingVariant $purchasingVariant, Sku $sku, User $user);

    /**
     * Đồng bộ dịch vụ từ đơn sang kiện
     *
     * @param PurchasingOrder $purchasingOrder
     * @param PurchasingPackage|null $purchasingPackage
     * @return void
     */
    public function syncServiceToPackage(PurchasingOrder $purchasingOrder, PurchasingPackage $purchasingPackage = null);
}
