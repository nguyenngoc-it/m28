<?php

namespace Modules\Document\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Modules\Document\Commands\CreateDocumentFreightBillInventory;
use Modules\Document\Commands\ImportFreightBillInventory;
use Modules\Document\Jobs\AfterConfirmDocumentFreightBillInventoryJob;
use Modules\Document\Jobs\CalculateBalanceMerchantWhenConfirmDocumentJob;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\FreightBill\Models\FreightBill;
use Exception;
use Modules\Order\Jobs\CalculateHasDocumentInventoryJob;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Rap2hpoutre\FastExcel\FastExcel;

class DocumentFreightBillInventoryService implements DocumentFreightBillInventoryServiceInterface
{
    /**
     * @param ShippingPartner $shippingPartner
     * @param UploadedFile $file
     * @param User $user
     * @param bool $confirm
     * @return array
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function create(ShippingPartner $shippingPartner, UploadedFile $file, User $user, bool $confirm = false): array
    {
        $importFreightBillInventory = (new ImportFreightBillInventory($file, $shippingPartner, $user))->handle();
        $message                    = $importFreightBillInventory['message'];
        $errors                     = $importFreightBillInventory['errors'];
        $freightBillInventories     = $importFreightBillInventory['processed_rows'];

        $document = null;
        if ($confirm) {
            $message = [];
        }
        if ((!empty($freightBillInventories)) && ($confirm || empty($errors)) && empty($message)) {
            $document = (new CreateDocumentFreightBillInventory($shippingPartner, $user))->handle();
            foreach ($freightBillInventories as $freightBillInventory) {
                /** @var FreightBill $freightBill */
                $freightBill = $freightBillInventory['freight_bill'];
                $order       = $freightBill->order;
                $warning     = Document::query()
                    ->join('document_freight_bill_inventories', 'documents.id', '=', 'document_freight_bill_inventories.document_id')
                    ->where('documents.status', Document::STATUS_COMPLETED)
                    ->where('documents.shipping_partner_id', $document->shipping_partner_id)
                    ->where('document_freight_bill_inventories.freight_bill_code', $freightBillInventory['freight_bill_code'])
                    ->count();

                $status = (
                    (
                        $order->cod == $freightBillInventory['cod_paid_amount']) ||
                    (
                        $freightBillInventory['cod_paid_amount'] === null
                    )
                ) ? DocumentFreightBillInventory::STATUS_CORRECT : DocumentFreightBillInventory::STATUS_INCORRECT;

                $freightBillInventory = array_merge($freightBillInventory, [
                    'cod_total_amount' => $order->cod,
                    'freight_bill_id' => $freightBill->id,
                    'order_packing_id' => $freightBill->order_packing_id,
                    'freight_bill_code' => $freightBill->freight_bill_code,
                    'order_id' => $freightBill->order_id,
                    'warning' => ($warning > 0) ? true : false,
                    'status' => $status
                ]);
                $document->documentFreightBillInventories()->create($freightBillInventory);

                if ($freightBill->cod_total_amount != $order->cod) {
                    $freightBill->cod_total_amount = $order->cod;
                    $freightBill->save();
                }

                if (!$order->has_document_inventory) {
                    $order->has_document_inventory = true;
                    $order->save();
                }
            }
        }
        if ($confirm) {
            $errors = [];
        }
        return [
            'message' => $message ? $message : [],
            'errors' => $errors,
            'document' => $document
        ];
    }

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user)
    {
        $sortBy    = Arr::pull($filter, 'sort_by', 'id');
        $sortByIds = Arr::pull($filter, 'sort_by_ids', false);
        $sort      = Arr::pull($filter, 'sort', 'desc');
        $page      = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage   = Arr::pull($filter, 'per_page', config('paginate.per_page'));
        $paginate  = Arr::pull($filter, 'paginate', true);
        $ids       = Arr::get($filter, 'ids', []);


        $query = Service::document()->query($filter)->getQuery()->where('type', Document::TYPE_FREIGHT_BILL_INVENTORY);
        if ($sortByIds) {
            $query->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        } else {
            $query->orderBy('documents' . '.' . $sortBy, $sort);
        }
        $query->with(['shippingPartner', 'creator']);

        if (!$paginate) {
            return $query->get();
        }

        $results = $query->paginate($perPage, ['documents.*'], 'page', $page);

        return [
            'documents' => $results->items(),
            'pagination' => $results,
        ];
    }

    /**
     * @param Document $document
     * @param array $filter
     * @param User $user
     * @return mixed|string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportFreightBill(Document $document, array $filter, User $user)
    {
        $data = [];
        if (!empty($filter['status'])) {
            $documentFreightBillInventories = $document->documentFreightBillInventories()->where('status', $filter['status'])->get();
        } else {
            $documentFreightBillInventories = $document->documentFreightBillInventories;
        }

        foreach ($documentFreightBillInventories as $documentFreightBillInventory) {
            $data[] = [
                trans('freight_bill_code') => $documentFreightBillInventory->freight_bill_code,
                trans('cod_total_amount') => $documentFreightBillInventory->cod_total_amount,
                trans('cod_paid_amount') => $documentFreightBillInventory->cod_paid_amount,
                trans('cod_fee_amount') => $documentFreightBillInventory->cod_fee_amount,
                trans('shipping_amount') => $documentFreightBillInventory->shipping_amount,
                trans('status') => trans('document_freight_bill_inventory.status.' . $documentFreightBillInventory->status),
                trans('total') => $documentFreightBillInventory->getTotalAmount(),
                trans('warning') => $documentFreightBillInventory->warning ? trans('document_freight_bill_inventory.warning') : '',
            ];
        }

        $list = collect($data);

        return (new FastExcel($list))->export('document-freight-bill-inventories.xlsx');
    }

    /**
     * @param Document $document
     * @param User $user
     * @return Document
     */
    public function confirm(Document $document, User $user)
    {
        $key      = 'document_confirm_' . $document->id;
        $document = Service::locking()->execute(function () use ($document, $user) {

            $document = $document->refresh();
            if ($document->status == Document::STATUS_COMPLETED) {
                throw new Exception('STATUS_INVALID_' . $document->code);
            }

            $document->status      = Document::STATUS_COMPLETED;
            $document->verifier_id = $user->id;
            $document->verified_at = Carbon::now();
            $document->save();

            return $document;
        }, $document->tenant_id, $key);

        if (!$document instanceof Document) {
            return;
        }

        dispatch(new CalculateBalanceMerchantWhenConfirmDocumentJob($document->id));
        dispatch(new AfterConfirmDocumentFreightBillInventoryJob($document->id, $user->id));

        $document->logActivity(DocumentEvent::CONFIRM, $user, [
            'document' => $document
        ]);

        return $document;
    }


    /**
     * @param Document $document
     * @param array $inputs
     * @param User $user
     * @return mixed
     */
    public function update(Document $document, $inputs = [], User $user)
    {
        $documentImportingInfo = $document->info;
        $updatedPayload        = [];
        foreach ($inputs as $key => $input) {
            if (isset($documentImportingInfo[$key]) && $documentImportingInfo[$key] != $input) {
                $updatedPayload[$key]['old'] = $documentImportingInfo[$key];
                $updatedPayload[$key]['new'] = $input;
                $documentImportingInfo[$key] = $input;
            }
        }
        if ($updatedPayload) {
            $document->info = $documentImportingInfo;
            $document->save();
            $document->logActivity(DocumentEvent::UPDATE, $user, [
                'document' => $document,
                'updated' => $updatedPayload
            ]);
        }

        if (isset($inputs['other_fee'])) {
            $otherFee = ($inputs['other_fee']) ? $inputs['other_fee'] / $document->documentFreightBillInventories->count() : 0;
            /** @var DocumentFreightBillInventory $freightBillInventory */
            foreach ($document->documentFreightBillInventories as $freightBillInventory) {
                $freightBillInventory->other_fee = $otherFee;
                $freightBillInventory->save();
            }
        }

        return $document;
    }

    /**
     *  Huỷ chứng từ
     * @param Document $document
     * @param User $user
     * @return Document|null
     */
    public function cancel(Document $document, User $user)
    {
        $key      = 'document_cancel_' . $document->id;
        $document = Service::locking()->execute(function () use ($document, $user) {
            $document = $document->refresh();
            if ($document->status == Document::STATUS_CANCELLED) {
                throw new Exception('STATUS_INVALID_' . $document->code);
            }

            $document->status = Document::STATUS_CANCELLED;
            $document->save();

            return $document;
        }, $document->tenant_id, $key);

        if (!$document instanceof Document) {
            return null;
        }

        /** @var DocumentFreightBillInventory $freightBillInventory */
        foreach ($document->documentFreightBillInventories as $freightBillInventory) {
            dispatch(new CalculateHasDocumentInventoryJob($freightBillInventory->order_id));
        }

        $document->logActivity(DocumentEvent::CANCEL, $user, [
            'document' => $document
        ]);


    }

    public function updateInfoFreightBill(Document $document, $inputs = [], User $user)
    {
        $paymentSlip                 = data_get($inputs, 'payment_slip');
        $receivedDate                = data_get($inputs, 'received_date');
        $receivedPayDate             = data_get($inputs, 'received_pay_date');
        $note                        = data_get($inputs, 'note');
        $oldPaymentSlip              = $document->info['payment_slip'] ?? null;
        $oldReceivedDate             = $document->received_date ?? null;
        $oldReceivedPayDate          = $document->received_pay_date ?? null;
        $oldNote                     = $document->note ?? null;
        $document->info              = $paymentSlip ? ['payment_slip' => $paymentSlip] : ['payment_slip' => $oldPaymentSlip];
        $document->received_date     = ($receivedDate) ? Carbon::createFromFormat('d/m/Y H:i', $receivedDate)->toDateTime() : $oldReceivedDate;
        $document->received_pay_date = $receivedPayDate ? Carbon::createFromFormat('d/m/Y H:i', $receivedPayDate)->toDateTime() : $oldReceivedPayDate;
        $document->note              = $note ? (string)$note : $oldNote;
        $document->save();
        $document->logActivity(DocumentEvent::UPDATE, $user, [
            'document' => $document,
        ]);
        return $document;
    }
}
