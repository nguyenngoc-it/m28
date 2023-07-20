<?php

namespace Modules\Document\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Events\DocumentDeliverComparisonCreated;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentDeliveryComparison;
use Modules\Service;
use Rap2hpoutre\FastExcel\FastExcel;

class CreatingDocumentDeliveryComparison extends CheckingDocumentDeliveryComparison
{
    protected $validRows = [];

    /**
     * @return Document
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     * @throws Exception
     */
    public function handle(): Document
    {
        $line = 1;
        (new FastExcel())->import($this->file, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

        if (empty($this->processedRows) && empty($this->warnings)) {
            throw new Exception('not found processed row');
        }

        $document = DB::transaction(function () {
            $document = $this->createDocument();
            /**
             * Import processed rows
             */
            foreach ($this->processedRows as $processedRow) {
                $document->documentDeliveryComparisons()->create([
                    'document_id' => $document->id,
                    'freight_bill_id' => $processedRow['freight_bill_id'],
                    'order_id' => $processedRow['order_id'],
                    'freight_bill_code' => $processedRow['freight_bill_code'],
                    'skus_count_order' => $processedRow['skus_count'],
                    'skus_count_carrier' => $processedRow['skus_count'],
                    'cod_total_order' => $processedRow['cod_total'],
                    'cod_total_carrier' => $processedRow['cod_total'],
                    'status' => DocumentDeliveryComparison::STATUS_CORRECT
                ]);
            }

            /**
             * Import error rows
             */
            foreach ($this->warnings as $warning) {
                if (!empty($warning['errors'])) {
                    $document->documentDeliveryComparisons()->create([
                        'document_id' => $document->id,
                        'freight_bill_id' => Arr::get($warning, 'row.freight_bill_id'),
                        'order_id' => Arr::get($warning, 'row.order_id'),
                        'freight_bill_code' => Arr::get($warning, 'row.freight_bill_code'),
                        'skus_count_order' => Arr::get($warning, 'row.skus_count_order') ?: Arr::get($warning, 'row.skus_count'),
                        'skus_count_carrier' => Arr::get($warning, 'row.skus_count'),
                        'cod_total_order' => Arr::get($warning, 'row.cod_total_order') ?: Arr::get($warning, 'row.cod_total'),
                        'cod_total_carrier' => Arr::get($warning, 'row.cod_total'),
                        'errors' => $warning['errors'],
                        'status' => DocumentDeliveryComparison::STATUS_INCORRECT
                    ]);
                }
            }
            return $document;
        });
        (new DocumentDeliverComparisonCreated($document))->queue();

        return $document;
    }

    /**
     * @return Document
     */
    protected function createDocument(): Document
    {
        $input = [
            'type' => Document::TYPE_DELIVERY_COMPARISON,
            'status' => Document::STATUS_COMPLETED,
            'tenant_id' => $this->shippingPartner->tenant_id,
            'shipping_partner_id' => $this->shippingPartner->id,
            'creator_id' => $this->user->id,
            'verifier_id' => $this->user->id,
            'verified_at' => Carbon::now()
        ];

        return Service::document()->create($input, $this->user);
    }
}
