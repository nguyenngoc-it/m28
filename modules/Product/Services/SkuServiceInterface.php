<?php

namespace Modules\Product\Services;

use Modules\Product\Models\BatchOfGood;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;

interface SkuServiceInterface
{
    /**
     * @param Sku $skuParent
     * @param BatchOfGood $batchOfGood
     * @param User $user
     * @return Sku
     */
    public function createBatchedSku(Sku $skuParent, BatchOfGood $batchOfGood, User $user);

    /**
     * @param Sku $sku
     * @param array $inputs
     * @param User $user
     * @return BatchOfGood
     */
    public function createBatchOfGood(Sku $sku, array $inputs, User $user);

    /**
     * Tìm những skus lô con để thay thế cho sku lô cha
     *
     * @param Sku $sku
     * @param int $quantity
     * @param float $price
     * @return array
     */
    public function findOrderSkuChildren(Sku $sku, int $quantity, float $price = 0): array;
}
