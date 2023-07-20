<?php

namespace Modules\Document\Services;

use Illuminate\Http\UploadedFile;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuInventory;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

interface DocumentSkuInventoryServiceInterface
{

    /**
     * Tạo chứng từ kiểm kê sản phẩm trong kho
     *
     * @param Warehouse $warehouse
     * @param User $user
     * @return Document
     */
    public function create(Warehouse $warehouse, User $user);

    /**
     * Quét sku để kiểm kê kho
     * @param Document $documentSkuInventory
     * @param WarehouseArea $warehouseArea
     * @param Sku $sku
     * @param User $user
     * @param int|null $quantity
     * @return mixed
     */
    public function scanSku(Document $documentSkuInventory, WarehouseArea $warehouseArea, Sku $sku, User $user, int $quantity = null);

    /**
     * Cập nhật số lượng kiểm kê của 1 sản phẩm
     *
     * @param DocumentSkuInventory $skuInventory
     * @param array $inputs
     * @param User $user
     * @return DocumentSkuInventory
     */
    public function updateSkuInventory(DocumentSkuInventory $skuInventory, array $inputs, User $user);

    /**
     * Sử dụng file excel import 1 loạt sản phẩm kiểm kê
     * @param UploadedFile $file
     * @param Document $documentSkuInventory
     * @param User $user
     * @param Merchant | null $merchant
     * @return mixed
     */
    public function importSkuInventories(UploadedFile $file, Document $documentSkuInventory, User $user, $merchant = null);

    /**
     * Lịch sử quét mã kiểm kê kho
     *
     * @param Document $documentInventory
     * @return array
     */
    public function scanHistories(Document $documentInventory);

    /**
     * Cân bằng số lượng skus kiểm kê
     *
     * @param Document $documentInventory
     * @param User $user
     * @return Document
     */
    public function balanceSkus(Document $documentInventory, User $user);

    /**
     * @param Document $documentSkuInventory
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function update(Document $documentSkuInventory, array $inputs, User $user);

    /**
     * Xác nhận kết thúc kiểm kê
     *
     * @param Document $documentSkuInventory
     * @param User $user
     * @return Document
     */
    public function completeDocument(Document $documentSkuInventory, User $user);

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user);
}
