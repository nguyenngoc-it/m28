<?php

namespace Modules\Document\Commands;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Events\DocumentReturnGoodsCreated;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuImporting;
use Modules\Document\Models\ImportingBarcode;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class CreateDocumentImportingReturnGoods
{
    /**
     * @var array
     */
    protected $inputs;
    /**
     * @var User
     */
    protected $user;
    /** @var Warehouse $warehouse */
    protected $warehouse;

    /**
     * CreateDocumentImportingReturnGoods constructor.
     * @param array $inputs
     * @param User $user
     */
    public function __construct(array $inputs, User $user)
    {
        $this->inputs    = $inputs;
        $this->user      = $user;
        $this->warehouse = Warehouse::query()->where('id', Arr::get($this->inputs, 'warehouse_id'))->first();
    }

    /**
     * @return Document
     */
    public function handle(): Document
    {
        $orderItems = Arr::get($this->inputs, 'order_items');
        return DB::transaction(function () use ($orderItems) {
            $document = $this->createDocument();
            $this->createImportingBarcode($document, $orderItems);
            $this->createDocumentSkuImporting($document, $orderItems);
            (new DocumentReturnGoodsCreated($document))->queue();
            return $document;
        });
    }

    /**
     * @param Document $document
     * @param array $orderItems
     */
    protected function createImportingBarcode(Document $document, array $orderItems)
    {
        foreach ($orderItems as $orderItem) {
            /** @var Order $order */
            $order        = Order::find($orderItem['id']);
            $freightBill  = $order->orderPacking->freightBill;
            $snapshotSkus = Service::documentImporting()->makeSnapshotReturnGoods($order, $orderItem['skus']);
            ImportingBarcode::create([
                'document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'type' => ImportingBarcode::TYPE_FREIGHT_BILL,
                'imported_type' => ImportingBarcode::IMPORTED_TYPE_RETURN_GOODS,
                'barcode' => $freightBill ? $freightBill->freight_bill_code : '',
                'object_id' => $freightBill ? $freightBill->id : 0,
                'freight_bill_id' => $freightBill ? $freightBill->id : 0,
                'snapshot_skus' => $snapshotSkus
            ]);
        }
    }

    /**
     * @param Document $document
     * @param array $orderItems
     */
    protected function createDocumentSkuImporting(Document $document, array $orderItems)
    {
        $orderIds = collect($orderItems)->pluck('id')->all();
        $dSkus    = [];
        foreach ($orderItems as $orderItem) {
            foreach ($orderItem['skus'] as $sku) {
                if (empty($dSkus[$sku['id']])) {
                    $dSkus[$sku['id']] = $sku['quantity'];
                } else {
                    $dSkus[$sku['id']] += $sku['quantity'];
                }
            }
        }
        $skuIds = array_keys($dSkus);
        $skus   = Sku::query()->whereIn('id', $skuIds)->get();
        /** @var Sku $sku */
        foreach ($dSkus as $skuId => $quantity) {
            /** @var Sku $sku */
            $sku              = $skus->where('id', $skuId)->first();
            $stock            = Service::stock()->getPriorityStockWhenImportSku($this->warehouse, $sku);
            $expectedQuantity = OrderSku::query()->whereIn('order_id', $orderIds)
                ->where('sku_id', $skuId)->sum('quantity');
            DocumentSkuImporting::create([
                'document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'warehouse_id' => $this->warehouse->id,
                'warehouse_area_id' => $stock->warehouse_area_id,
                'sku_id' => $sku->id,
                'quantity' => (int)$expectedQuantity,
                'real_quantity' => $quantity,
                'stock_id' => ($stock instanceof Stock) ? $stock->id : 0,
            ]);
        }
    }

    /**
     * @return Document
     * @throws Exception
     */
    protected function createDocument(): Document
    {
        $input = [
            'type' => Document::TYPE_IMPORTING_RETURN_GOODS,
            'status' => Document::STATUS_DRAFT,
            'note' => ''
        ];

        return Service::document()->create($input, $this->user, Warehouse::find(Arr::get($this->inputs, 'warehouse_id')));
    }
}
