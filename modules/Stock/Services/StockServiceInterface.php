<?php

namespace Modules\Stock\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

interface StockServiceInterface
{
    /**
     * Import stock from file
     *
     * @param Tenant $tenant
     * @param string $filePath
     * @param User $creator
     * @return array
     */
    public function importStocks(Tenant $tenant, $filePath, User $creator);

    /**
     * Make stock
     *
     * @param Sku $sku
     * @param WarehouseArea $warehouseArea
     * @return Stock
     */
    public function make(Sku $sku, WarehouseArea $warehouseArea);

    /**
     * Import stock
     *
     * @param Sku $sku
     * @param WarehouseArea $warehouseArea
     * @param int $quantity
     * @param User $creator
     * @return Stock
     */
    public function import(Sku $sku, WarehouseArea $warehouseArea, $quantity, User $creator);

    /**
     * Khởi tạo đối tượng query stock
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function stockQuery(array $filter);


    /**
     * Khởi tạo đối tượng query stockLog
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function stockLogQuery(array $filter);

    /**
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listStocks(array $filter, User $user);

    /**
     * Lấy danh sách phí lưu kho stock theo ngày
     *
     * @param array $filter
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|mixed
     */
    public function storageFeeDaily(array $filter);

    /**
     * Lấy danh sách phí lưu kho sku stock theo ngày và kho
     *
     * @param array $filter
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|mixed
     */
    public function storageFeeDailyByWarehouse(array $filter);

    /**
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listStockLogs(array $filter, User $user);

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportStockLogs(array $filter, User $user);

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export(array $filter, User $user);

    /**
     * Lấy vị trí kho ưu tiên của sku khi nhập kho
     *
     * @param Warehouse $warehouse
     * @param Sku $sku
     * @return Stock
     */
    public function getPriorityStockWhenImportSku(Warehouse $warehouse, Sku $sku);

    /**
     * Di chuyển vị trí kho của sản phẩm
     *
     * @param array $dataStocks
     * @param User $user
     * @param Warehouse $warehouse
     * @return void
     */
    public function changePositionStocks(array $dataStocks, User $user, Warehouse $warehouse);

    /**
     * Giảm số lượng tồn tạm tính của stock
     *
     * @param Stock $stock
     * @param int $quantity
     */
    public function decrementQuantity(Stock $stock, $quantity);

    /**
     * Tính toán lại tồn tạm tính của stock
     *
     * @param Stock $stock
     */
    public function calculateQuantity(Stock $stock);
}
