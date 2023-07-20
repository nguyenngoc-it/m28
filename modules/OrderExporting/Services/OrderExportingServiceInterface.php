<?php

namespace Modules\OrderExporting\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\OrderPacking\Models\OrderPacking;

interface OrderExportingServiceInterface
{
    /**
     * @param array $filter
     * @return LengthAwarePaginator|Builder|Builder[]|Collection
     */
    public function listing(array $filter);

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * tạo các OrderExportingItems
     *
     * @param OrderExporting $orderExporting
     * @param OrderPacking $orderPacking
     */
    public function updateOrderExportingItems(OrderExporting $orderExporting, OrderPacking $orderPacking);

    /**
     * Cập nhật OrderExporting theo orderPacking
     *
     * @param OrderExporting $orderExporting
     * @return void
     */
    public function updateByOrderPacking(OrderExporting $orderExporting);
}
