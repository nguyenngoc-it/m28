<?php

namespace Modules\PurchasingOrder\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Support\Arr;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\PurchasingOrder\Commands\CheckPemissionView;
use Modules\PurchasingOrder\Commands\UpdateMerchantPurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingVariant;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;
use Modules\PurchasingPackage\Models\PurchasingPackageService;
use Modules\Service;
use Modules\User\Models\User;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;

class PurchasingOrderService implements PurchasingOrderServiceInterface
{

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user)
    {
        $sortBy    = Arr::get($filter, 'sort_by', 'id');
        $sortByIds = Arr::get($filter, 'sort_by_ids', false);
        $sort      = Arr::get($filter, 'sort', 'desc');
        $page      = Arr::get($filter, 'page', config('paginate.page'));
        $perPage   = Arr::get($filter, 'per_page', config('paginate.per_page'));
        $paginate  = Arr::get($filter, 'paginate', true);
        $ids       = Arr::get($filter, 'ids', []);
        $skuCode   = Arr::get($filter, 'sku_code');
        if ($skuCode) {
            $skuIds  = [];
            $product = Product::query()->where([
                'tenant_id' => $user->tenant_id,
                'code' => $skuCode
            ])->first();
            if ($product instanceof Product) {
                $skuIds = $product->skus->pluck('id');
            }
            $sku = Sku::query()->where([
                'tenant_id' => $user->tenant_id,
                'code' => $skuCode
            ])->first();
            if ($sku instanceof Sku) {
                array_push($skuIds, $sku->id);
            }
            $filter['purchasing_variant_ids'] = PurchasingVariant::query()->whereIn('sku_id', array_unique($skuIds))->pluck('id')->all();
            unset($filter['sku_code']);
        }

        foreach (['sort', 'sort_by', 'page', 'per_page', 'sort_by_ids', 'paginate', 'tab_vendor'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::purchasingOrder()->query($filter)->getQuery();

        $query->with(['purchasingService', 'purchasingAccount']);
        if ($sortByIds) {
            $query->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        } else {
            $query->orderBy('purchasing_orders' . '.' . $sortBy, $sort);
        }

        if (!$paginate) {
            return $query->get();
        }

        $results = $query->paginate($perPage, ['purchasing_orders.*'], 'page', $page);
        $items   = array_map(function (PurchasingOrder $purchasingOrder) use ($user) {
            $purchasingOrder->permission_views = Service::purchasingOrder()->pemissionViews($purchasingOrder, $user);
            return $purchasingOrder;
        }, $results->items());
        return [
            'purchasing_orders' => $items,
            'pagination' => $results,
        ];
    }

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new PurchasingOrderQuery())->query($filter);
    }

    /**
     * Quyền hiển thị các actions trên giao diện
     *
     * @param PurchasingOrder $purchasingOrder
     * @param User $user
     * @return array
     */
    public function pemissionViews(PurchasingOrder $purchasingOrder, User $user)
    {
        return (new CheckPemissionView($purchasingOrder, $user))->handle();
    }

    /**
     * @param PurchasingOrder $purchasingOrder
     * @param $status
     * @param User $creator
     * @return mixed|PurchasingOrder
     */
    public function changeState(PurchasingOrder $purchasingOrder, $status, User $creator)
    {
        if ($purchasingOrder->status == $status) {
            return $purchasingOrder;
        }

        $purchasingOrder->status = $status;
        $purchasingOrder->save();

        if ($purchasingOrder->warehouse_id) {
            $skuIds = [];
            foreach ($purchasingOrder->purchasingOrderItems as $purchasingOrderItem) {
                $purchasingVariant = $purchasingOrderItem->purchasingVariant;
                if (!$purchasingVariant instanceof PurchasingVariant) {
                    continue;
                }

                if (isset($skuIds[$purchasingVariant->sku_id])) continue;

                $skuIds[$purchasingVariant->sku_id] = true;
            }
        }

        $purchasingOrder->logActivity(PurchasingOrderEvent::CHANGE_STATUS, $creator, $purchasingOrder->getChanges());


        return $purchasingOrder;
    }

    /**
     * @param PurchasingOrder $purchasingOrder
     * @param array $input
     * @param User $user
     * @return mixed
     */
    public function updateMerchantPurchasingOrder(PurchasingOrder $purchasingOrder, array $input, User $user)
    {
        return (new UpdateMerchantPurchasingOrder($purchasingOrder, $input, $user))->handle();
    }

    /**
     * Map variant của 1 purchasing Order
     *
     * @param PurchasingOrder $purchasingOrder
     * @param PurchasingVariant $purchasingVariant
     * @param Sku $sku
     * @param User $user
     * @return void
     */
    public function mappingVariant(PurchasingOrder $purchasingOrder, PurchasingVariant $purchasingVariant, Sku $sku, User $user)
    {
        /**
         * Cập nhật Sku
         */
        $payloadLog                = [
            'purchasing_variant' => $purchasingVariant,
            'old_sku' => $purchasingVariant->sku ? $purchasingVariant->sku->code : '',
            'new_status' => $sku->code,
        ];
        $purchasingVariant->sku_id = $sku->id;
        $purchasingVariant->save();
        $purchasingVariant->logActivity('MAPPING_SKU', $user, $payloadLog);

        /**
         * Map sku cho các kiện của đơn
         */
        $purchasingOrder->purchasingPackages->each(function (PurchasingPackage $purchasingPackage) {
            $purchasingPackage->purchasingPackageItems->each(function (PurchasingPackageItem $purchasingPackageItem) use ($purchasingPackage) {

                $purchasingVariant = $purchasingPackageItem->purchasingVariant;
                if (
                    $purchasingVariant && $purchasingVariant->sku_id &&
                    $purchasingPackageItem->sku_id != $purchasingVariant->sku_id
                ) {

                    $purchasingPackageItem->sku_id = $purchasingVariant->sku_id;
                    $purchasingPackageItem->save();
                }
            });
        });
    }

    /**
     * Đồng bộ dịch vụ từ đơn sang kiện
     *
     * @param PurchasingOrder $purchasingOrder
     * @param PurchasingPackage|null $purchasingPackage
     * @return void
     */
    public function syncServiceToPackage(PurchasingOrder $purchasingOrder, PurchasingPackage $purchasingPackage = null)
    {
        $syncServices = [];
        /** @var \Modules\PurchasingOrder\Models\PurchasingOrderService $purchasingOrderService */
        foreach ($purchasingOrder->purchasingOrderServices as $purchasingOrderService) {
            $syncServices[$purchasingOrderService->service_id]['service_price_id'] = $purchasingOrderService->service_price_id;
            $syncServices[$purchasingOrderService->service_id]['price']            = $purchasingOrderService->servicePrice->price;
        }
        if ($purchasingPackage) {
            $purchasingPackage->services()->sync($syncServices);
            $quantity = $purchasingPackage->purchasingPackageItems()->sum('quantity');
            $purchasingPackage->purchasingPackageServices()->each(function (PurchasingPackageService $purchasingPackageService) use ($quantity) {
                $purchasingPackageService->quantity = $quantity;
                $purchasingPackageService->amount   = round($quantity * $purchasingPackageService->servicePrice->price, 2);
                $purchasingPackageService->save();
            });
        } else {
            $purchasingOrder->purchasingPackages->each(function (PurchasingPackage $purchasingPackage) use ($syncServices) {
                $purchasingPackage->services()->sync($syncServices);
                $quantity = $purchasingPackage->purchasingPackageItems()->sum('quantity');
                $purchasingPackage->purchasingPackageServices()->each(function (PurchasingPackageService $purchasingPackageService) use ($quantity) {
                    $purchasingPackageService->quantity = $quantity;
                    $purchasingPackageService->amount   = round($quantity * $purchasingPackageService->servicePrice->price, 2);
                    $purchasingPackageService->save();
                });
            });
        }
    }
}
