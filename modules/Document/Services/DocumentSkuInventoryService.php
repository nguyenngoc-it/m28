<?php

namespace Modules\Document\Services;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Carbon\Carbon;
use Gobiz\Activity\ActivityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Commands\BalanceSku;
use Modules\Document\Commands\ImportSkuInventory;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuInventory;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;
use Exception;

class DocumentSkuInventoryService implements DocumentSkuInventoryServiceInterface
{
    /**
     * Tạo chứng từ kiểm kê sản phẩm trong kho
     *
     * @param Warehouse $warehouse
     * @param User $user
     * @return Document
     */
    public function create(Warehouse $warehouse, User $user)
    {
        return Service::document()->create(['type' => Document::TYPE_SKU_INVENTORY, 'status' => Document::STATUS_DRAFT], $user, $warehouse);
    }

    /**
     * Quét sku để kiểm kê kho
     * @param Document $documentSkuInventory
     * @param WarehouseArea $warehouseArea
     * @param Sku $sku
     * @param User $user
     * @param int|null $quantity
     * @return mixed|DocumentSkuInventory
     */
    public function scanSku(Document $documentSkuInventory, WarehouseArea $warehouseArea, Sku $sku, User $user, int $quantity = null)
    {
        $skuStockWarehouseArea = $sku->stocks->where('warehouse_area_id', $warehouseArea->id)->first();
        /**
         * Khởi tạo vị trí kho mặc định chứa sku nếu sku chưa có trong kho
         */
        if (empty($skuStockWarehouseArea)) {
            $skuStockWarehouseArea = Stock::create([
                'tenant_id' => $user->tenant_id,
                'product_id' => $sku->product->id,
                'sku_id' => $sku->id,
                'warehouse_id' => $documentSkuInventory->warehouse_id,
                'warehouse_area_id' => $warehouseArea->id,
                'quantity' => 0,
                'real_quantity' => 0
            ]);
        }

        /** @var DocumentSkuInventory $skuInventory */
        $skuInventory  = DocumentSkuInventory::query()->where([
            'document_id' => $documentSkuInventory->id,
            'sku_id' => $sku->id,
            'warehouse_area_id' => $warehouseArea->id,
        ])->first();
        $quantityStock = $skuStockWarehouseArea->real_quantity;
        if ($skuInventory) {
            $quantityChecked  = is_null($quantity) && is_null($skuInventory->quantity_checked) ? null : (is_null($skuInventory->quantity_checked) ? (int)$quantity : DB::raw('quantity_checked + ' . (int)$quantity));
            $quantityBalanced = is_null($quantity) && is_null($skuInventory->quantity_checked) ? null : (is_null($skuInventory->quantity_checked) ? ((int)$quantity - (int)$quantityStock) : DB::raw('quantity_checked - quantity_in_stock'));

            $skuInventory->update([
                'quantity_in_stock' => $quantityStock,
                'quantity_in_stock_before_balanced' => $quantityStock,
                'quantity_checked' => $quantityChecked,
                'quantity_balanced' => $quantityBalanced,
                'warehouse_id' => $warehouseArea->warehouse_id,
                'warehouse_area_id' => $warehouseArea->id,
            ]);
        } else {
            $skuInventory = DocumentSkuInventory::create(
                [
                    'document_id' => $documentSkuInventory->id,
                    'sku_id' => $sku->id,
                    'stock_id' => $skuStockWarehouseArea->id,
                    'quantity_in_stock' => $quantityStock,
                    'quantity_in_stock_before_balanced' => $quantityStock,
                    'quantity_checked' => $quantity,
                    'quantity_balanced' => is_null($quantity) ? null : $quantity - $quantityStock,
                    'warehouse_id' => $warehouseArea->warehouse_id,
                    'warehouse_area_id' => $warehouseArea->id,
                ]
            );
        }
        /**
         * Lưu log lịch sử quét
         */
        $documentSkuInventory->logActivity(DocumentEvent::SCAN_INVENTORY, $user, ['sku' => $sku->code, 'quantity' => $quantity, 'warehouse_area' => $warehouseArea->name]);
        return $skuInventory->refresh();
    }

    /**
     * Cập nhật số lượng kiểm kê của 1 sản phẩm
     *
     * @param DocumentSkuInventory $skuInventory
     * @param array $inputs
     * @param User $user
     * @return DocumentSkuInventory
     */
    public function updateSkuInventory(DocumentSkuInventory $skuInventory, array $inputs, User $user)
    {
        $quantityChecked                 = Arr::get($inputs, 'quantity');
        $quantityChecked                 = $quantityChecked === '' ? null : (int)$quantityChecked;
        $skuInventory->quantity_checked  = $quantityChecked;
        $skuInventory->quantity_balanced = is_null($quantityChecked) ? null : (int)$quantityChecked - $skuInventory->quantity_in_stock;
        $skuInventory->explain           = Arr::get($inputs, 'explain');
        $skuInventory->save();
        $skuInventory->document->logActivity(DocumentEvent::UPDATE_QUANTITY_SKU_INVENTORY, $user, ['sku' => $skuInventory->sku->only(['id', 'code']), 'quantity' => $quantityChecked]);
        return $skuInventory;
    }


    /**
     *  Sử dụng file excel import 1 loạt sản phẩm kiểm kê
     * @param UploadedFile $file
     * @param Document $documentSkuInventory
     * @param User $user
     * @param null $merchant
     * @return array|mixed
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function importSkuInventories(UploadedFile $file, Document $documentSkuInventory, User $user, $merchant = null)
    {
        return (new ImportSkuInventory($file, $documentSkuInventory, $user, $merchant))->handle();
    }

    /**
     * Lịch sử quét mã kiểm kê kho
     *
     * @param Document $documentInventory
     * @return array
     */
    public function scanHistories(Document $documentInventory)
    {
        return ActivityService::logger()->get('document', $documentInventory->id, ['action' => DocumentEvent::SCAN_INVENTORY]);
    }

    /**
     * Cân bằng số lượng skus kiểm kê
     *
     * @param Document $documentInventory
     * @param User $user
     * @return Document
     */
    public function balanceSkus(Document $documentInventory, User $user)
    {
        return (new BalanceSku($documentInventory, $user))->handle();
    }

    /**
     * @param Document $documentSkuInventory
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function update(Document $documentSkuInventory, array $inputs, User $user)
    {
        $note = Arr::get($inputs, 'note');
        if (!is_null($note)) {
            $documentSkuInventory->note = $note;
            $documentSkuInventory->save();
        }
        return $documentSkuInventory;
    }

    /**
     * Xác nhận kết thúc kiểm kê
     *
     * @param Document $documentSkuInventory
     * @param User $user
     * @return Document
     */
    public function completeDocument(Document $documentSkuInventory, User $user)
    {
        $key      = 'document_confirm_' . $documentSkuInventory->id;
        $document = Service::locking()->execute(function () use ($documentSkuInventory, $user) {
            $documentSkuInventory = $documentSkuInventory->refresh();
            if ($documentSkuInventory->status == Document::STATUS_COMPLETED) {
                throw new Exception('STATUS_INVALID_' . $documentSkuInventory->code);
            }

            $documentSkuInventory->status      = Document::STATUS_COMPLETED;
            $documentSkuInventory->verified_at = Carbon::now();
            $documentSkuInventory->verifier_id = $user->id;
            $documentSkuInventory->save();

            return $documentSkuInventory;
        }, $documentSkuInventory->tenant_id, $key);

        if (!$document instanceof Document) {
            return;
        }

        $documentSkuInventory->logActivity(DocumentEvent::COMPLETE_INVENTORY, $user);
        return $documentSkuInventory;
    }

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

        foreach (['sort', 'sort_by', 'page', 'per_page', 'sort_by_ids', 'paginate'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::document()->query($filter)->getQuery()->where('type', Document::TYPE_SKU_INVENTORY);
        if ($sortByIds) {
            $query->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        } else {
            $query->orderBy('documents' . '.' . $sortBy, $sort);
        }

        if (!$paginate) {
            return $query->get();
        }

        $results = $query->paginate($perPage, ['documents.*'], 'page', $page);
        return [
            'document_sku_inventories' => $results->items(),
            'pagination' => $results,
        ];
    }
}
