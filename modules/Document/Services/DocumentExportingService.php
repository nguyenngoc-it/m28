<?php

namespace Modules\Document\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Services\Permission;
use Modules\Document\Commands\CreateDocumentExportingInventory;
use Modules\Document\Events\DocumentExportingCreated;
use Modules\Document\Events\DocumentExportingExported;
use Modules\Document\Models\Document;
use Modules\Order\Events\OrderExported;
use Modules\Order\Events\OrderShippingFinancialStatusChanged;
use Modules\Order\Models\Order;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class DocumentExportingService implements DocumentExportingServiceInterface
{

    /**
     * Kiểm tra ycxh không hợp lệ trước khi tạo chứng từ xuất hàng
     *
     * @param array $orderPackingIds
     * @return array
     */
    public function checkingWarning(array $orderPackingIds)
    {
        $orderExportings = OrderExporting::query()->whereIn('order_packing_id', $orderPackingIds)->with([
            'order', 'freightBill', 'merchant', 'orderExportingItems.sku'
        ])->get();

        return [
            'invalid_order_exportings' => $orderExportings->whereIn('status', [OrderExporting::STATUS_PROCESS, OrderExporting::STATUS_FINISHED])->values(),
            'valid_order_exportings' => $orderExportings->where('status', OrderExporting::STATUS_NEW)->values()
        ];
    }

    /**
     * Tạo chứng từ xuất hàng
     *
     * @param Warehouse $warehouse
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function create(Warehouse $warehouse, array $inputs, User $user): Document
    {
        $datas = data_get($inputs, 'order_exporting_ids');
        $scan  = data_get($inputs, 'scan');
        if ($scan) {
            $dataInputs = [];
            foreach ($datas as $data) {
                $dataInputs[] = $data['id'];
            }
        }
        $initInputs = [
            'type' => Document::TYPE_EXPORTING,
            'info' => [
                Document::INFO_DOCUMENT_EXPORTING_BARCODE_TYPE => Arr::get($inputs, Document::INFO_DOCUMENT_EXPORTING_BARCODE_TYPE, ''),
                Document::INFO_DOCUMENT_EXPORTING_DOCUMENT_PACKING => Arr::get($inputs, 'document_packing', ''),
                Document::INFO_DOCUMENT_EXPORTING_RECEIVER_NAME => Arr::get($inputs, 'receiver_name', ''),
                Document::INFO_DOCUMENT_EXPORTING_RECEIVER_PHONE => Arr::get($inputs, 'receiver_phone', ''),
                Document::INFO_DOCUMENT_EXPORTING_RECEIVER_LICENSE => Arr::get($inputs, 'receiver_license', ''),
                Document::INFO_DOCUMENT_EXPORTING_PARTNER => Arr::get($inputs, 'partner', ''),
            ],
            'status' => Document::STATUS_DRAFT,
        ];
        if (!$scan) {
            $document = DB::transaction(function () use ($initInputs, $inputs, $user, $warehouse) {
                $document = Service::document()->create($initInputs, $user, $warehouse);
                $document->orderExportings()->sync(Arr::get($inputs, 'order_exporting_ids', []));
                $document->orderExportings()->update(['status' => OrderExporting::STATUS_PROCESS]);
                return $document;
            });
        } else {
            $document = DB::transaction(function () use ($initInputs, $inputs, $user, $warehouse, $dataInputs, $datas) {
                $document = Service::document()->create($initInputs, $user, $warehouse);
                $document->orderExportings()->sync($dataInputs);
                foreach ($datas as $data) {

                    $scanCreatedAt = $data['scan_created_at'] ? Carbon::createFromFormat('H:i:s d/m/Y', $data['scan_created_at'])->format('Y-m-d H:i:s') : '';
                    $document->orderExportings()->where('order_exportings.id', $data['id'])->update(['status' => OrderExporting::STATUS_PROCESS,
                        'scan_created_at' => $scanCreatedAt]);
                }
                $document->orderExportings()->update(['status' => OrderExporting::STATUS_PROCESS]);
                return $document;
            });
        }

        (new DocumentExportingCreated($document))->queue();

        return $document;
    }

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user)
    {
        $sortBy   = Arr::get($filter, 'sort_by', 'id');
        $sort     = Arr::get($filter, 'sort', 'desc');
        $page     = Arr::get($filter, 'page', config('paginate.page'));
        $perPage  = Arr::get($filter, 'per_page', config('paginate.per_page'));
        $paginate = Arr::get($filter, 'paginate', true);

        foreach (['sort', 'sort_by', 'page', 'per_page', 'paginate'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::document()->query($filter)->getQuery();
        if (!Gate::check(Permission::OPERATION_HISTORY_EXPORT)) {
            $query->where('verifier_id', $user->id);
        }
        $query->with(['warehouse', 'creator', 'verifier']);
        $query->orderBy('documents' . '.' . $sortBy, $sort);

        if (!$paginate) {
            return $query->get();
        }

        $results = $query->paginate($perPage, ['document.*'], 'page', $page);
        return [
            'document_exportings' => $results->items(),
            'pagination' => $results,
        ];
    }

    /**
     * Cập nhật thông tin (người nhận) chứng từ xuất hàng
     *
     * @param Document $documentExporting
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function update(Document $documentExporting, array $inputs, User $user)
    {
        $documentExportingInfo = $documentExporting->info;
        $updatedPayload        = [];
        foreach ($inputs as $key => $input) {
            if (isset($documentExportingInfo[$key]) && $documentExportingInfo[$key] != $input) {
                $updatedPayload[$key]['old'] = $documentExportingInfo[$key];
                $updatedPayload[$key]['new'] = $input;
                $documentExportingInfo[$key] = $input;
            }
        }
        if ($updatedPayload) {
            $documentExporting->info = $documentExportingInfo;
            $documentExporting->save();
            $documentExporting->logActivity(DocumentEvent::UPDATE, $user, [
                'document' => $documentExporting,
                'updated' => $updatedPayload
            ]);
        }
        return $documentExporting;
    }

    /**
     * Huỷ chứng từ xuất hàng
     *
     * @param Document $documentExporting
     * @param User $user
     * @return Document
     */
    public function cancel(Document $documentExporting, User $user)
    {
        $documentExporting = DB::transaction(function () use ($documentExporting, $user) {
            $documentExporting->status = Document::STATUS_CANCELLED;
            $documentExporting->save();

            /**
             * Cập nhật yêu cầu xuất hàng trong chứng từ về chờ xử lý
             */
            $documentExporting->orderExportings()->update(['status' => OrderExporting::STATUS_NEW]);
            return $documentExporting;
        });

        $documentExporting->logActivity(DocumentEvent::CANCEL, $user, [
            'document' => $documentExporting
        ]);

        return $documentExporting;
    }

    /**
     * Xuất kho chứng từ xuất hàng
     *
     * @param Document $documentExporting
     * @param User $user
     * @return Document
     */
    public function exporting(Document $documentExporting, User $user)
    {
        $documentExporting = DB::transaction(function () use ($documentExporting, $user) {
            $documentExporting->status      = Document::STATUS_COMPLETED;
            $documentExporting->verifier_id = $user->id;
            $documentExporting->verified_at = Carbon::now();
            $documentExporting->save();

            /**
             * Cập nhật yêu cầu xuất hàng trong chứng từ thành đã xử lý
             */
            $documentExporting->orderExportings()->update(['status' => OrderExporting::STATUS_FINISHED]);
            /**
             * Cập nhật đơn của yêu cầu xuất hàng trong chứng từ sang "đang giao"
             */
            $documentExporting->orderExportings->each(function (OrderExporting $orderExporting) use ($user) {
                if ($orderExporting->order->canChangeStatus(Order::STATUS_DELIVERING)) {
                    $orderExporting->order->changeStatus(Order::STATUS_DELIVERING, $user);
                    (new OrderExported($orderExporting->order, $user))->queue();
                    (new OrderShippingFinancialStatusChanged($orderExporting->order, Order::SFS_WAITING_COLLECT, $orderExporting->order->shipping_financial_status, $user))->queue();
                }
            });
            /**
             * Cập nhật số lượng tồn thực tế của skus
             */
            Service::documentExporting()->updateSkuStocks($documentExporting, $user);
            return $documentExporting;
        });

        (new DocumentExportingExported($documentExporting, $user, Carbon::now()))->queue();

        return $documentExporting;
    }

    /**
     * Cập nhật số lượng tồn của skus sau khi xuất chứng từ xuất kho
     *
     * @param Document $documentExporting
     * @param User $user
     * @return void
     */
    public function updateSkuStocks(Document $documentExporting, User $user)
    {
        foreach ($documentExporting->orderExportings as $orderExporting) {
            if ($order = $orderExporting->order) {
                foreach ($order->orderStocks as $orderStock) {
                    $stock = $orderStock->stock;
                    $stock->export($orderStock->quantity, $user, $order, Stock::ACTION_EXPORT_FOR_ORDER)
                        ->with(['document' => $documentExporting->code])->run();
                }
            }
        }
    }

    /**
     * Tạo chứng từ đối soát cho chứng từ xuất hàng
     *
     * @param Document $documentExporting
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function createInventory(Document $documentExporting, array $inputs, User $user)
    {
        return (new CreateDocumentExportingInventory($documentExporting, $inputs, $user))->handle();
    }
}
