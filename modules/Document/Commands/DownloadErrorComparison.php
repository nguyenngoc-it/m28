<?php

namespace Modules\Document\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentDeliveryComparison;
use Rap2hpoutre\FastExcel\FastExcel;

class DownloadErrorComparison
{
    /**
     * @var Document
     */
    protected $documentDeliveryComparison;

    /**
     * DownloadReceivedSkus constructor.
     * @param Document $documentDeliveryComparison
     */
    public function __construct(Document $documentDeliveryComparison)
    {
        $this->documentDeliveryComparison = $documentDeliveryComparison;
    }

    /**
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function handle()
    {
        return (new FastExcel($this->documentDeliveryComparison->documentDeliveryComparisons->where('status', DocumentDeliveryComparison::STATUS_INCORRECT)))
            ->export('downloadErrorComparison.xlsx', function (DocumentDeliveryComparison $documentDeliveryComparison) {
                $reasons = $this->makeErrorReasons($documentDeliveryComparison->errors);
                return [
                    trans('tracking_number', [], 'vi') => $documentDeliveryComparison->freight_bill_code,
                    trans('quantity', [], 'vi') . ' ' . trans('product', [], 'vi') => $documentDeliveryComparison->skus_count_carrier,
                    trans('total') . ' ' . trans('cod') => $documentDeliveryComparison->cod_total_carrier,
                    trans('total') . ' ' . trans('weight') => $documentDeliveryComparison->weight_total_carrier,
                    trans('reason') => $reasons ? implode(', ', $reasons) : ''
                ];
            });
    }

    /**
     * @param array $errors
     * @return array
     */
    protected function makeErrorReasons(array $errors)
    {
        $reasons = [];
        foreach ($errors as $error) {
            foreach ($error as $field => $reason) {
                if ($field == 'skus_count') {
                    $reasons[] = trans('order_invalid_quantity');
                }
                if ($field == 'cod_total') {
                    $reasons[] = trans('order_invalid_cod');
                }
                if ($field == 'weight_total') {
                    $reasons[] = trans('order_invalid_weight');
                }
            }
        }

        return $reasons;
    }
}
