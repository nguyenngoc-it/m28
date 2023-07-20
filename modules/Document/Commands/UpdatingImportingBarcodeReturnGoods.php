<?php

namespace Modules\Document\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuImporting;
use Modules\Document\Models\ImportingBarcode;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\User\Models\User;

class UpdatingImportingBarcodeReturnGoods
{
    /**
     * @var array
     */
    protected $inputs;
    /**
     * @var User
     */
    protected $user;
    /** @var Document $documentImporting */
    protected $documentImporting;

    /**
     * CreateDocumentImportingReturnGoods constructor.
     * @param Document $documentInporting
     * @param array $inputs
     * @param User $user
     */
    public function __construct(Document $documentInporting, array $inputs, User $user)
    {
        $this->inputs            = $inputs;
        $this->user              = $user;
        $this->documentImporting = $documentInporting;
    }

    /**
     * @return Document
     */
    public function handle()
    {
        $orderItems = Arr::get($this->inputs, 'order_items');
        DB::transaction(function () use ($orderItems) {
            $this->updateImportingBarcode($orderItems);
            $this->updateDocumentSkuImporting($orderItems);
        });
        return $this->documentImporting->refresh();
    }

    /**
     * @param array $orderItems
     */
    protected function updateImportingBarcode(array $orderItems)
    {
        foreach ($orderItems as $orderItem) {
            /** @var Order $order */
            $order        = Order::find($orderItem['id']);
            $freightBill  = $order->orderPacking->freightBill;
            $snapshotSkus = Service::documentImporting()->makeSnapshotReturnGoods($order, $orderItem['skus']);
            /** @var ImportingBarcode $importingBarcode */
            $importingBarcode                = ImportingBarcode::query()->where([
                'document_id' => $this->documentImporting->id,
                'type' => ImportingBarcode::TYPE_FREIGHT_BILL,
                'barcode' => $freightBill->freight_bill_code
            ])->first();
            $importingBarcode->snapshot_skus = $snapshotSkus;
            $importingBarcode->save();
            $importingBarcode->logActivity('update_snapshots', $this->user, $snapshotSkus);
        }
    }

    /**
     * @param array $orderItems
     */
    protected function updateDocumentSkuImporting(array $orderItems)
    {
        $dSkus = [];
        foreach ($orderItems as $orderItem) {
            foreach ($orderItem['skus'] as $sku) {
                if (empty($dSkus[$sku['id']])) {
                    $dSkus[$sku['id']] = $sku['quantity'];
                } else {
                    $dSkus[$sku['id']] += $sku['quantity'];
                }
            }
        }
        foreach ($dSkus as $skuId => $quantity) {
            /** @var DocumentSkuImporting $documentSkuImporting */
            $documentSkuImporting                = DocumentSkuImporting::query()->where([
                'document_id' => $this->documentImporting->id,
                'sku_id' => $skuId
            ])->first();
            $documentSkuImporting->real_quantity = $quantity;
            $documentSkuImporting->save();
        }
    }
}
