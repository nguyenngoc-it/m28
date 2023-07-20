<?php

namespace Modules\Product\Services;

use Carbon\Carbon;
use http\Exception\InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Modules\Product\Events\BatchOfGoodCreated;
use Modules\Product\Models\BatchOfGood;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\User\Models\User;

class SkuService implements SkuServiceInterface
{
    /**
     * @param Sku $skuParent
     * @param BatchOfGood $batchOfGood
     * @param User $user
     * @return Sku
     */
    public function createBatchedSku(Sku $skuParent, BatchOfGood $batchOfGood, User $user)
    {
        if (empty($batchOfGood->code)) {
            throw new InvalidArgumentException('batch_of_goods is empty code!');
        }
        $inputs = [
            'code' => $skuParent->code . '-' . $batchOfGood->code,
            'name' => $skuParent->name . '-' . $batchOfGood->code,
            'sku_parent_id' => $skuParent->id,
            'batch_of_good_id' => $batchOfGood->id,

        ];
        return Service::product()->createSKU($skuParent->product, $inputs, $user);
    }

    /**
     * @param Sku $sku
     * @param array $inputs
     * @param User $user
     * @return BatchOfGood
     */
    public function createBatchOfGood(Sku $sku, array $inputs, User $user)
    {
        return DB::transaction(function () use ($sku, $inputs, $user) {
            $batchOfGood = BatchOfGood::create(array_merge($inputs, ['sku_id' => $sku->id]));
            $skuChild    = $this->createBatchedSku($sku, $batchOfGood, $user);
            $batchOfGood->update([
                'sku_child_id' => $skuChild->id
            ]);
            (new BatchOfGoodCreated($sku, $batchOfGood, $user, Carbon::now()))->queue();
            return $batchOfGood->refresh();
        });
    }

    /**
     * Tìm những skus lô con để thay thế cho sku lô cha
     *
     * @param Sku $sku
     * @param int $quantity
     * @param float $price
     * @return array
     */
    public function findOrderSkuChildren(Sku $sku, int $quantity, float $price = 0): array
    {
        if ($sku->skuChildren->count() < 1) {
            return [];
        }
        $orderSkuChildren     = [];
        $priorityBatchOfGoods = $sku->batchOfGoods;
        switch ($sku->logic_batch) {
            case BatchOfGood::LOGIC_FIFO:
                $priorityBatchOfGoods = $sku->batchOfGoods->sortBy('production_at');
                break;
            case BatchOfGood::LOGIC_LIFO:
                $priorityBatchOfGoods = $sku->batchOfGoods->sortBy('production_at', SORT_REGULAR, true);
                break;
            case BatchOfGood::LOGIC_FEFO:
                $priorityBatchOfGoods = $sku->batchOfGoods->sortBy('expiration_at');
                break;
        }

        $remainingQuantity = $quantity;
        /** @var BatchOfGood $batOfGood */
        foreach ($priorityBatchOfGoods as $batOfGood) {
            $skuChild      = $batOfGood->skuChild;
            $stockSkuChild = (int)$skuChild->stocks->sum('quantity');
            /**
             * Nếu lô đã hết hạn thì ko đc chọn
             */
            if ($stockSkuChild == 0 || $batOfGood->expiration_at->subDay()->startOfDay()->lessThan(Carbon::now()->startOfDay())) {
                continue;
            }
            if ($stockSkuChild >= $remainingQuantity) {
                $orderSkuChildren[] = [
                    'sku_id' => $skuChild->id,
                    'tenant_id' => $skuChild->tenant_id,
                    'quantity' => $remainingQuantity,
                    'price' => $price,
                    'order_amount' => $price * $remainingQuantity,
                    'total_amount' => $price * $remainingQuantity
                ];
                return $orderSkuChildren;
            } else {
                $orderSkuChildren[] = [
                    'sku_id' => $skuChild->id,
                    'tenant_id' => $skuChild->tenant_id,
                    'quantity' => $stockSkuChild,
                    'price' => $price,
                    'order_amount' => $price * $stockSkuChild,
                    'total_amount' => $price * $stockSkuChild
                ];
                $remainingQuantity  -= $stockSkuChild;
            }
        }

        return [];
    }
}
