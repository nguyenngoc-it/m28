<?php

namespace Modules\Document\Services;

use Modules\Document\Models\Document;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

interface DocumentExportingServiceInterface
{
    /**
     * Kiểm tra ycxh không hợp lệ trước khi tạo chứng từ xuất hàng
     *
     * @param array $orderPackingIds
     * @return array
     */
    public function checkingWarning(array $orderPackingIds);

    /**
     * Tạo chứng từ xuất hàng
     *
     * @param Warehouse $warehouse
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function create(Warehouse $warehouse, array $inputs, User $user): Document;

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user);

    /**
     * Cập nhật thông tin (người nhận) chứng từ xuất hàng
     *
     * @param Document $documentExporting
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function update(Document $documentExporting, array $inputs, User $user);

    /**
     * Huỷ chứng từ xuất hàng
     *
     * @param Document $documentExporting
     * @param User $user
     * @return Document
     */
    public function cancel(Document $documentExporting, User $user);

    /**
     * Xuất kho chứng từ xuất hàng
     *
     * @param Document $documentExporting
     * @param User $user
     * @return Document
     */
    public function exporting(Document $documentExporting, User $user);

    /**
     * Cập nhật số lượng tồn của skus sau khi xuất chứng từ xuất kho
     *
     * @param Document $documentExporting
     * @param User $user
     * @return void
     */
    public function updateSkuStocks(Document $documentExporting, User $user);

    /**
     * Tạo chứng từ đối soát cho chứng từ xuất hàng
     *
     * @param Document $documentExporting
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function createInventory(Document $documentExporting, array $inputs, User $user);
}
