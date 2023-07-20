<?php

namespace Modules\Document\Services;

use Modules\Document\Models\Document;
use Modules\Order\Models\Order;
use Modules\Stock\Commands\ChangeStock;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Gobiz\ModelQuery\ModelQuery;

interface DocumentImportingServiceInterface
{
    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function skuImportingQuery(array $filter);

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user);

    /**
     * @param Document $document
     * @param array $filter
     * @return array
     */
    public function listSkuImporting(Document $document, array $filter);

    /**
     * Cập nhật thông tin (người nhận) chứng từ nhập hàng
     *
     * @param Document $documentImporting
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function update(Document $documentImporting, array $inputs, User $user);

    /**
     * Huỷ chứng từ nhập hàng
     *
     * @param Document $documentImporting
     * @param User $user
     * @return Document
     */
    public function cancel(Document $documentImporting, User $user);

    /**
     * Xác nhận chứng từ nhập hàng
     *
     * @param Document $document
     * @param User $user
     * @param string $action
     * @return Document
     */
    public function confirm(Document $document, User $user, string $action = Stock::ACTION_IMPORT);

    /**
     *  Cập nhật số lượng tồn của skus sau khi xác nhận chứng từ nhập kho
     * @param Document $documentImporting
     * @param User $user
     * @param string $action
     * @return void
     */
    public function updateSkuStocks(Document $documentImporting, User $user, string $action = Stock::ACTION_IMPORT);

    /**
     * Tạo bản ghi thể hiện hàng hoàn đã nhập
     *
     * @param Order $order
     * @param array $skus [{id:1, quantity:1}]
     * @return array
     */
    public function makeSnapshotReturnGoods(Order $order, array $skus);

    /**
     * Download ds skus đã nhận nhập kho
     *
     * @param Document $documentImporting
     * @return string
     */
    public function downloadReceivedSkus(Document $documentImporting);

    /**
     * Tạo mới hoặc thay đổi thông tin bản ghi skus nhập kho
     *
     * @param Document $documentImporting
     * @param array $skus
     * @param User $user
     * @return void
     */
    public function updateOrCreateSkuImportings(Document $documentImporting, array $skus, User $user);
}
