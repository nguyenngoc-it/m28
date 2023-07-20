<?php

namespace Modules\Document\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Exception;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\Auth\Services\Permission;
use Modules\Document\Commands\DownloadReceivedSkus;
use Modules\Document\Jobs\AfterConfirmDocumentImportingJob;
use Modules\Document\Jobs\CalculateBalanceMerchantWhenConfirmDocumentJob;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuImporting;
use Modules\Document\Models\ImportingBarcode;
use Modules\Document\Transformers\DocumentSkuImportingTransformer;
use Modules\Document\Transformers\DocumentTransformer;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Events\OrderReturned;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;

class DocumentImportingService implements DocumentImportingServiceInterface
{
    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function skuImportingQuery(array $filter)
    {
        return (new DocumentSkuImportingQuery())->query($filter);
    }

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user)
    {
        $sortBy  = Arr::pull($filter, 'sort_by', 'id');
        $sort    = Arr::pull($filter, 'sort', 'desc');
        $page    = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage = Arr::pull($filter, 'per_page', config('paginate.per_page'));

        $query = Service::document()->query($filter)->getQuery();
        if (!$user->can(Permission::OPERATION_HISTORY_IMPORT)) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('creator_id', $user->id);
                $q->orWhere('verifier_id', $user->id);
            });
        }
        $query->with([
            'tenant', 'warehouse', 'creator', 'verifier', 'importingBarcodes',
            'documentSkuImportings', 'documentSkuImportings.sku'
        ]);
        $query->orderBy('documents' . '.' . $sortBy, $sort);

        $results = $query->paginate($perPage, 'documents.*', 'page', $page);

        return [
            'documents' => array_map(function ($document_importing) {
                return (new DocumentTransformer())->transform($document_importing);
            }, $results->items()),
            'pagination' => $results,
        ];
    }


    /**
     * @param Document $document
     * @param array $filter
     * @return array
     */
    public function listSkuImporting(Document $document, array $filter)
    {
        $sortBy  = Arr::pull($filter, 'sort_by', 'sku_id');
        $sort    = Arr::pull($filter, 'sort', 'asc');
        $page    = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage = Arr::pull($filter, 'per_page', config('paginate.per_page'));

        $query = $this->skuImportingQuery($filter)->getQuery();
        $query->where('document_id', $document->id);
        $query->with(['sku']);
        $query->orderBy('document_sku_importings' . '.' . $sortBy, $sort)->orderBy('document_sku_importings.created_at');

        $results = $query->paginate($perPage, 'document_sku_importings.*', 'page', $page);

        return [
            'document_sku_importings' => array_map(function ($document_sku_importing) {
                return (new DocumentSkuImportingTransformer())->transform($document_sku_importing);
            }, $results->items()),
            'pagination' => $results,
        ];
    }


    /**
     * Cập nhật thông tin (người nhận) chứng từ nhập hàng
     *
     * @param Document $documentImporting
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function update(Document $documentImporting, array $inputs, User $user)
    {
        $documentImportingInfo = $documentImporting->info;
        $updatedPayload        = [];
        foreach ($inputs as $key => $input) {
            if (isset($documentImportingInfo[$key]) && $documentImportingInfo[$key] != $input) {
                $updatedPayload[$key]['old'] = $documentImportingInfo[$key];
                $updatedPayload[$key]['new'] = $input;
                $documentImportingInfo[$key] = $input;
            }
        }
        if ($updatedPayload) {
            $documentImporting->info = $documentImportingInfo;
            $documentImporting->save();
            $documentImporting->logActivity(DocumentEvent::UPDATE, $user, [
                'document' => $documentImporting,
                'updated' => $updatedPayload
            ]);
        }
        return $documentImporting;
    }

    /**
     * Huỷ chứng từ nhập hàng
     *
     * @param Document $documentImporting
     * @param User $user
     * @return Document
     */
    public function cancel(Document $documentImporting, User $user)
    {
        $key      = 'document_cancel_' . $documentImporting->id;
        $document = Service::locking()->execute(function () use ($documentImporting, $user) {
            $documentImporting = $documentImporting->refresh();
            if ($documentImporting->status == Document::STATUS_CANCELLED) {
                throw new Exception('STATUS_INVALID_' . $documentImporting->code);
            }

            $documentImporting->status = Document::STATUS_CANCELLED;
            $documentImporting->save();

            return $documentImporting;
        }, $documentImporting->tenant_id, $key);

        if (!$document instanceof Document) {
            return $documentImporting;
        }

        $document->logActivity(DocumentEvent::CANCEL, $user, [
            'document' => $document
        ]);

        return $document;
    }

    /**
     * Xác nhận chứng từ nhập hàng
     *
     * @param Document $document
     * @param User $user
     * @param string $action
     * @return Document
     */
    public function confirm(Document $document, User $user, string $action = Stock::ACTION_IMPORT)
    {
        $key               = 'document_confirm_' . $document->id;
        $documentImporting = Service::locking()->execute(function () use ($document, $user, $action) {

            $document = $document->refresh();
            if ($document->status == Document::STATUS_COMPLETED) {
                throw new Exception('STATUS_INVALID_' . $document->code);
            }


            $document->status      = Document::STATUS_COMPLETED;
            $document->verifier_id = $user->id;
            $document->verified_at = Carbon::now();
            $document->save();

            $this->updateSkuStocks($document, $user, $action);

            /**
             * Nếu xác nhận chứng từ nhập hàng hoàn thì chuyển vận đơn sang đã trả hàng
             */
            if ($document->type == Document::TYPE_IMPORTING_RETURN_GOODS) {
                foreach ($document->importingBarcodes as $importingBarcode) {
                    if (empty($importingBarcode->freightBill)) {
                        continue;
                    }
                    (new OrderReturned($importingBarcode->freightBill->order, $user))->queue();
                    Service::freightBill()->changeStatus($importingBarcode->freightBill, FreightBill::STATUS_RETURN_COMPLETED, $user);
                }
            }

            return $document;
        }, $document->tenant_id, $key);

        if (!$documentImporting instanceof Document) {
            return $document;
        }

        //Khi xác nhận chứng từ nhập hàng, trừ chi phí nhập hàng vào ví
        dispatch(new CalculateBalanceMerchantWhenConfirmDocumentJob($documentImporting->id));
        dispatch(new AfterConfirmDocumentImportingJob($documentImporting->id));

        $documentImporting->logActivity(DocumentEvent::IMPORT, $user, [
            'document' => $documentImporting
        ]);

        return $documentImporting;
    }

    /**
     *  Cập nhật số lượng tồn của skus sau khi xác nhận chứng từ nhập kho
     *
     * @param Document $documentImporting
     * @param User $user
     * @param string $action
     * @return void
     */
    public function updateSkuStocks(Document $documentImporting, User $user, string $action = Stock::ACTION_IMPORT)
    {
        /** @var DocumentSkuImporting $documentSkuImporting */
        foreach ($documentImporting->documentSkuImportings as $documentSkuImporting) {
            $stock = $documentSkuImporting->stock;
            $stock->import($documentSkuImporting->real_quantity, $user, $documentImporting->importingStockLogObject(), $action)->with(['documentSkuImporting' => $documentSkuImporting])->run();
        }
    }

    /**
     * Tạo bản ghi thể hiện hàng hoàn đã nhập
     *
     * @param Order $order
     * @param array $skus [{id:1, quantity:1}]
     * @return array
     */
    public function makeSnapshotReturnGoods(Order $order, array $skus)
    {
        $mSkus = Sku::query()->whereIn('id', collect($skus)->pluck('id')->all())->get()->toArray();
        $dSkus = [];
        foreach ($skus as $sku) {
            $kmsku = array_search($sku['id'], array_column($mSkus, 'id'));
            if ($kmsku !== false) {
                $mSku    = $mSkus[$kmsku];
                $dSkus[] = [
                    'id' => $mSku['id'],
                    'code' => $mSku['code'],
                    'name' => $mSku['name'],
                    'product_id' => $mSku['product_id'],
                    'quantity' => $sku['quantity']
                ];
            }
        }

        return [
            'skus' => $dSkus,
            'order' => [
                'id' => $order->id,
                'code' => $order->code
            ],
            'freight_bill' => $order->orderPacking->freightBill ? $order->orderPacking->freightBill->freight_bill_code : ''
        ];
    }

    /**
     * Download ds skus đã nhận nhập kho
     *
     * @param Document $documentImporting
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function downloadReceivedSkus(Document $documentImporting)
    {
        return (new DownloadReceivedSkus($documentImporting))->handle();
    }

    /**
     * Tạo mới hoặc thay đổi thông tin bản ghi skus nhập kho
     *
     * @param Document $documentImporting
     * @param array $skus [
     *      ['sku_id','warehouse_area_id','quantity']
     * ]
     * @param User $user
     * @return void
     * @throws Exception
     */
    public function updateOrCreateSkuImportings(Document $documentImporting, array $skus, User $user)
    {
        foreach ($skus as $sku) {
            if ($skuId = Arr::get($sku, 'sku_id')) {
                $idSkuImporting  = Arr::get($sku, 'sku_importing_id', 0);
                $realQuantity    = Arr::get($sku, 'real_quantity', 0);
                $warehouseAreaId = Arr::get($sku, 'warehouse_area_id');
                $isDeleted       = Arr::get($sku, 'is_deleted', false);
                $sku             = Sku::find($skuId);
                if ($sku instanceof Sku) {
                    $stockSku = $sku->stocks->where('warehouse_area_id', $warehouseAreaId)->first();
                    if (empty($stockSku)) {
                        $stockSku = Stock::create([
                            'tenant_id' => $sku->tenant_id,
                            'product_id' => $sku->product->id,
                            'sku_id' => $sku->id,
                            'warehouse_id' => $documentImporting->warehouse->id,
                            'warehouse_area_id' => $warehouseAreaId,
                            'quantity' => 0,
                            'real_quantity' => 0
                        ]);
                        //Service::product()->updateSkuStocks($sku, $realQuantity, $stockSku, $user, $documentImporting);
                    }
                    if ($idSkuImporting && $skuImporting = DocumentSkuImporting::find($idSkuImporting)) {
                        if ($isDeleted) {
                            $skuImporting->delete();
                            continue;
                        }
                        $skuImporting->stock_id          = $stockSku->id;
                        $skuImporting->warehouse_id      = $documentImporting->warehouse->id;
                        $skuImporting->warehouse_area_id = $warehouseAreaId;
                        $skuImporting->real_quantity     = $realQuantity;
                        $skuImporting->save();
                        continue;
                    }
                    DocumentSkuImporting::updateOrCreate(
                        [
                            'tenant_id' => $sku->tenant_id,
                            'document_id' => $documentImporting->id,
                            'sku_id' => $skuId,
                            'stock_id' => $stockSku->id
                        ],
                        [
                            'warehouse_id' => $documentImporting->warehouse->id,
                            'warehouse_area_id' => $warehouseAreaId,
                            'real_quantity' => $realQuantity
                        ]
                    );
                }
            }
        }

        // Cap nhat lai so luong thuc nhan cho kien nhap
        foreach ($documentImporting->importingBarcodes as $importingBarcode) {
            if (in_array($importingBarcode->type, [ImportingBarcode::TYPE_PACKAGE_CODE, ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL])) {
                $purchasingPackage = $documentImporting->tenant->purchasingPackages()->firstWhere('id', $importingBarcode->object_id);
                if (!$purchasingPackage instanceof PurchasingPackage) {
                    continue;
                }

                Service::purchasingPackage()->updateReceivedQuantityByDocument($purchasingPackage, $documentImporting);
            }
        }

    }
}
