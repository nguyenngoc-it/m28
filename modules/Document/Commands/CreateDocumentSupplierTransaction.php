<?php

namespace Modules\Document\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Document\Events\DocumentSupplierTransactionCreated;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSupplierTransaction;
use Modules\Document\Services\DocumentEvent;
use Modules\Service;
use Modules\Supplier\Models\Supplier;
use Modules\SupplierTransaction\Commands\CalculateSupplierTransaction;
use Modules\SupplierTransaction\Jobs\ProcessSupplierTransactionJob;
use Modules\SupplierTransaction\Models\SupplierTransaction;
use Modules\Transaction\Models\Transaction;
use Modules\User\Models\User;
use Exception;

class CreateDocumentSupplierTransaction
{
    /**
     * @var Supplier|null
     */
    protected $supplier;

    /**
     * @var array
     */
    protected $input;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @param Supplier $supplier
     * @param array $input
     * @param User $creator
     */
    public function __construct(Supplier $supplier, array $input, User $creator)
    {
        $this->supplier  = $supplier;
        $this->input     = $input;
        $this->creator   = $creator;
    }

    /**
     * @return Document
     */
    public function handle()
    {
        $document = $this->createDocument();

        if($document instanceof Document) {
            (new DocumentSupplierTransactionCreated($document, $this->creator))->queue();
        }

        return $document;
    }

    /**
     * @return Document
     */
    protected function createDocument()
    {
        $input = [
            'status' => Document::STATUS_COMPLETED,
            'type' => Document::TYPE_SUPPLIER_PAYMENT,
            'tenant_id' => $this->creator->tenant_id,
            'creator_id' => $this->creator->id
        ];

        if (!$codePrefix = Document::getCodePrefix($input['type'])) {
            throw new InvalidArgumentException("Can't find code prefix for document type {$input['type']}");
        }

        return DB::transaction(function () use ($input, $codePrefix) {
            $document = Document::create($input);
            $document->update(['code' => $codePrefix . $document->id]);

            $amount = Arr::get($this->input, 'amount');
            $documentSupplierTransactionInput = $this->input;
            $documentSupplierTransactionInput['action'] = ($amount > 0) ? DocumentSupplierTransaction::ACTION_COLLECT : DocumentSupplierTransaction::ACTION_DEPOSIT;
            $documentSupplierTransactionInput['amount'] = abs(floatval($amount));

            /** @var DocumentSupplierTransaction $documentSupplierTransaction */
            $documentSupplierTransaction = $document->documentSupplierTransaction()->create($documentSupplierTransactionInput);

            $transType = $this->getTransType($documentSupplierTransaction);
            if(empty($transType)) {
                throw new Exception('NOT_FOUND_ACTION_'.$documentSupplierTransaction->action);
            }

            (new CalculateSupplierTransaction($document, $transType))->handle();

            return $document;
        });
    }

    /**
     * @param DocumentSupplierTransaction $documentSupplierTransaction
     * @return string|null
     */
    protected function getTransType(DocumentSupplierTransaction $documentSupplierTransaction)
    {
        $transType = null;
        switch ($documentSupplierTransaction->action) {
            case DocumentSupplierTransaction::ACTION_DEPOSIT: {
                $transType = SupplierTransaction::TYPE_PAYMENT_DEPOSIT;
                break;
            }
            case DocumentSupplierTransaction::ACTION_COLLECT: {
                $transType = SupplierTransaction::TYPE_PAYMENT_COLLECT;
                break;
            }
        }

        return $transType;
    }
}
