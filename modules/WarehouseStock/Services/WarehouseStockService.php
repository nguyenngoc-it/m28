<?php

namespace Modules\WarehouseStock\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\Product\Models\Sku;
use Modules\Warehouse\Models\Warehouse;
use Modules\WarehouseStock\Commands\ImportWarehouseStocks;
use Modules\WarehouseStock\Commands\ListWarehouseStocks;
use Modules\WarehouseStock\Models\WarehouseStock;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\WarehouseArea;

class WarehouseStockService implements WarehouseStockServiceInterface
{
    /**
     * Make warehouseStock
     *
     * @param Sku $sku
     * @param Warehouse $warehouse
     * @return WarehouseStock|object
     */
    public function make(Sku $sku, Warehouse $warehouse)
    {
        return WarehouseStock::query()->firstOrCreate([
            'sku_id' => $sku->id,
            'warehouse_id' => $warehouse->id,
        ], [
            'tenant_id' => $sku->tenant_id,
            'product_id' => $sku->product_id,
        ]);
    }


    /**
     * Khởi tạo đối tượng query warehouseStock
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function warehouseStockQuery(array $filter)
    {
        return (new WarehouseStockQuery())->query($filter);
    }

    /**
     * @param array $filter
     * @param User $user
     * @return \Illuminate\Pagination\LengthAwarePaginator|object
     */
    public function listWarehouseStocks(array $filter, User $user)
    {
        return (new ListWarehouseStocks($filter, $user))->handle();
    }
}
