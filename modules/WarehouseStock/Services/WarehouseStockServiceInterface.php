<?php

namespace Modules\WarehouseStock\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\Product\Models\Sku;
use Modules\Warehouse\Models\Warehouse;
use Modules\WarehouseStock\Models\WarehouseStock;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\WarehouseArea;

interface WarehouseStockServiceInterface
{

    /**
     * Make warehouseStock
     *
     * @param Sku $sku
     * @param Warehouse $warehouse
     * @return WarehouseStock|object
     */
    public function make(Sku $sku, Warehouse $warehouse);

    /**
     * Khởi tạo đối tượng query warehouseStock
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function warehouseStockQuery(array $filter);

    /**
     * @param array $filter
     * @param User $user
     * @return \Illuminate\Pagination\LengthAwarePaginator|object
     */
    public function listWarehouseStocks(array $filter, User $user);
}
