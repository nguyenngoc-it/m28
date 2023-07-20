<?php /** @noinspection PhpReturnDocTypeMismatchInspection */

namespace Modules\Stock\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Exception;
use Gobiz\Log\LogService;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Document\Models\Document;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Stock\Commands\ExportStockLogs;
use Modules\Stock\Commands\ImportStocks;
use Modules\Stock\Commands\ListStockLogs;
use Modules\Stock\Commands\ListStocks;
use Modules\Stock\Commands\StockCalculateQuantity;
use Modules\Stock\Commands\StorageFeeDaily;
use Modules\Stock\Commands\StorageFeeDailyByWarehouse;
use Modules\Stock\Models\Stock;
use Modules\Stock\Commands\ExportStocks;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

class StockService implements StockServiceInterface
{
    /**
     * Make stock
     *
     * @param Sku $sku
     * @param WarehouseArea $warehouseArea
     * @return Stock|mixed
     */
    public function make(Sku $sku, WarehouseArea $warehouseArea)
    {
        return Stock::query()->firstOrCreate([
            'sku_id' => $sku->id,
            'warehouse_area_id' => $warehouseArea->id,
        ], [
            'tenant_id' => $sku->tenant_id,
            'product_id' => $sku->product_id,
            'warehouse_id' => $warehouseArea->warehouse_id,
            'quantity' => 0,
            'real_quantity' => 0,
        ]);
    }

    /**
     * Import stock
     *
     * @param Sku $sku
     * @param WarehouseArea $warehouseArea
     * @param int $quantity
     * @param User $creator
     * @return Stock
     */
    public function import(Sku $sku, WarehouseArea $warehouseArea, $quantity, User $creator)
    {
        $stock = $this->make($sku, $warehouseArea);
        $stock->do(Stock::ACTION_IMPORT, $quantity, $creator)->run();

        return $stock;
    }

    /**
     * Import stock from file
     *
     * @param Tenant $tenant
     * @param string $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importStocks(Tenant $tenant, $filePath, User $creator)
    {
        return (new ImportStocks($tenant, $filePath, $creator))->handle();
    }

    /**
     * Khởi tạo đối tượng query stock
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function stockQuery(array $filter)
    {
        return (new StockQuery())->query($filter);
    }


    /**
     * Khởi tạo đối tượng query stockLog
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function stockLogQuery(array $filter)
    {
        return (new StockLogQuery())->query($filter);
    }

    /**
     * @param array $filter
     * @param User $user
     * @return LengthAwarePaginator|object
     */
    public function listStocks(array $filter, User $user)
    {
        return (new ListStocks($filter, $user))->handle();
    }

    /**
     * Lấy danh sách phí lưu kho theo ngày
     *
     * @param array $filter
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function storageFeeDaily(array $filter)
    {
        return (new StorageFeeDaily($filter))->handle();
    }

    /**
     * Lấy danh sách phí lưu kho sku stock theo ngày
     *
     * @param array $filter
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function storageFeeDailyByWarehouse(array $filter)
    {
        return (new StorageFeeDailyByWarehouse($filter))->handle();
    }

    /**
     * @param array $filter
     * @param User $user
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|Builder
     */
    public function listStockLogs(array $filter, User $user)
    {
        return (new ListStockLogs($filter, $user))->handle();
    }

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportStockLogs(array $filter, User $user)
    {
        return (new ExportStockLogs($filter, $user))->handle();
    }

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export(array $filter, User $user)
    {
        return (new ExportStocks($filter, $user))->handle();
    }

    /**
     * Lấy vị trí kho ưu tiên của sku khi nhập kho
     * Lấy stock được update lần gần nhất và vị trí kho không phải vị trí di động (movable=0)
     * Nếu tồn thực tế = 0 thì kiểm tra vị trí đó xem có tồn của sku khác không, nếu ko có thì vẫn chọn vị trí đó
     * Nếu sku chưa có trong kho thì nhập vào vị trí mặc định
     *
     * @param Warehouse $warehouse
     * @param Sku $sku
     * @return Stock
     */
    public function getPriorityStockWhenImportSku(Warehouse $warehouse, Sku $sku)
    {
        /** @var Stock $stock */
        $stock = $sku->stocks()->select(['stocks.*'])->where('stocks.warehouse_id', $warehouse->id)
            ->join('warehouse_areas', 'stocks.warehouse_area_id', '=', 'warehouse_areas.id')
            ->where('warehouse_areas.movable', 0)
            ->orderBy('stocks.updated_at', 'desc')
            ->first();

        if ($stock instanceof Stock) {
            if ($stock->real_quantity) {
                return $stock;
            }
            $realQuantityOtherSkus = Stock::query()->where('sku_id', '<>', $sku->id)
                ->where('warehouse_area_id', '=', $stock->warehouse_area_id)
                ->sum('real_quantity');
            if (empty($realQuantityOtherSkus)) {
                return $stock;
            }
        }

        /**
         * Khởi tạo vị trí kho mặc định chứa sku nếu sku chưa có trong kho
         */
        return Stock::firstOrCreate(
            [
                'tenant_id' => $sku->tenant_id,
                'product_id' => $sku->product->id,
                'sku_id' => $sku->id,
                'warehouse_id' => $warehouse->id,
                'warehouse_area_id' => $warehouse->getDefaultArea()->id,
            ],
            [
                'quantity' => 0,
                'real_quantity' => 0
            ]
        );
    }

    /**
     * Di chuyển vị trí kho của sản phẩm
     *
     * @param array $dataStocks [['stock_id' => {}, 'quantity' => {}, 'warehouse_area_id' => {}]]
     * @param User $user
     * @param Warehouse $warehouse
     * @return void
     */
    public function changePositionStocks(array $dataStocks, User $user, Warehouse $warehouse)
    {
        DB::transaction(function () use ($dataStocks, $user, $warehouse) {
            $document = Service::document()->create([
                'type' => Document::TYPE_CHANGE_POSITION_GOODS,
                'status' => Document::STATUS_COMPLETED
            ], $user, $warehouse);
            foreach ($dataStocks as $dataStock) {
                /** @var Stock $stock */
                $stock           = Stock::find($dataStock['stock_id']);
                $quantity        = $dataStock['quantity'];
                $warehouseAreaId = $dataStock['warehouse_area_id'];
                $importStock     = Service::stock()->make($stock->sku, WarehouseArea::find($warehouseAreaId));

                $documentChangePosition = $document->documentChangePositionStocks()->create([
                    'stock_id_from' => $stock->id,
                    'stock_id_to' => $importStock->id,
                    'quantity' => $quantity,
                    'creator_id' => $user->id
                ]);

                /**
                 * export khỏi vị trí cũ
                 */
                $stock->export($quantity, $user, $document, Stock::ACTION_EXPORT_FOR_CHANGE_POSITION)
                    ->with($documentChangePosition->attributesToArray())->run();

                /**
                 * import vào vị trí mới
                 */
                $importStock->import($quantity, $user, $document, Stock::ACTION_IMPORT_FOR_CHANGE_POSITION)
                    ->with($documentChangePosition->attributesToArray())->run();
            }
        });
    }

    /**
     * Tính toán lại tồn tạm tính của stock
     *
     * @param Stock $stock
     */
    public function calculateQuantity(Stock $stock)
    {
        (new StockCalculateQuantity($stock))->handle();
    }

    /**
     * Giảm số lượng tồn tạm tính của stock
     *
     * @param Stock $stock
     * @param int $quantity
     */
    public function decrementQuantity(Stock $stock, $quantity)
    {
        $oldStock = $stock->attributesToArray();

        $stock->update(['quantity' => DB::raw('quantity - ' . $quantity)]);

        LogService::logger('stock')->debug('DECREMENT_STOCK_QUANTITY', [
            'old_stock' => $oldStock,
            'quantity' => $quantity,
        ]);
    }
}
