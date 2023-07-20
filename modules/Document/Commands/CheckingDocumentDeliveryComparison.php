<?php

namespace Modules\Document\Commands;

use App\Base\Validator;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Http\UploadedFile;
use Modules\FreightBill\Models\FreightBill;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class CheckingDocumentDeliveryComparison
{
    /** @var ShippingPartner */
    protected $shippingPartner;
    /** @var User $user */
    protected $user;
    /** @var UploadedFile */
    protected $file;
    /** @var array $processedRows */
    protected $processedRows = [];
    /** @var array $errors */
    protected $errors = [];
    protected $warnings = [];

    /**
     * ImportSkuInventory constructor.
     * @param UploadedFile $file
     * @param ShippingPartner $shippingPartner
     * @param User $user
     */
    public function __construct(ShippingPartner $shippingPartner, UploadedFile $file, User $user)
    {
        $this->shippingPartner = $shippingPartner;
        $this->user            = $user;
        $this->file            = $file;
    }

    /**
     * @return array
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        $line = 1;
        (new FastExcel())->import($this->file, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

        if (empty($this->errors) && empty($this->processedRows) && empty($this->warnings)) {
            $this->errors[] = ['file' => 'empty'];
        }

        return [
            'errors' => array_values(array_merge($this->errors, $this->warnings)),
            'processed_rows' => count($this->processedRows) + count($this->warnings)
        ];
    }

    /**
     * @param array $row
     * @param int $line
     */
    protected function processRow(array $row, $line)
    {
        $row = $this->makeRow($row);
        $this->validator($row, $line);
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $row = array_map(function ($value) {
            return trim($value);
        }, $row);

        $rowData = array_filter($row, function ($value) {
            return $value != '';
        });

        if (!count($rowData)) {
            return [];
        }

        $params = [
            'freight_bill_code',
            'skus_count', // Tổng số lượng sp
            'cod_total' // Tổng cod
        ];

        if (isset($row[''])) {
            unset($row['']);
        }
        $values = array_values($row);
        if (count($values) != count($params)) {
            return [];
        }
        $row = array_combine($params, $values);

        return $row;
    }

    /**
     * @param array $row
     * @param int $line
     */
    protected function validator(array $row, int $line)
    {
        if (!$row) {
            $this->errors[$line][] = [
                'line' => $line,
                'errors' => 'INVALID_COLUMN',
            ];
            return;
        }

        $freightBill = $this->user->tenant->freightBills()
            ->where('shipping_partner_id', $this->shippingPartner->id)
            ->where('freight_bill_code', $row['freight_bill_code'])->first();

        if (!$freightBill instanceof FreightBill) {
            $this->errors[$line]['line']     = $line;
            $this->errors[$line]['errors'][] = ['freight_bill_code' => Validator::ERROR_INVALID];
            $this->errors[$line]['row']      = $row;
            return;
        }
        $order = $freightBill->order;

        $row['skus_count'] = $row['skus_count'] ? (int)$row['skus_count'] : 0;
        $skuCountOrder     = $order->orderSkus->sum('quantity');
        if ($row['skus_count'] <= 0 || $row['skus_count'] != $skuCountOrder) {
            $this->warnings[$line]['line']     = $line;
            $this->warnings[$line]['errors'][] = ['skus_count' => Validator::ERROR_INVALID];
            $row['skus_count_order']         = $skuCountOrder;
        }

        $row['cod_total'] = $row['cod_total'] ? (float)$row['cod_total'] : 0;
        $codTotalOrder    = round($order->cod, 2);
        if ($row['cod_total'] < 0 || round($row['cod_total'], 2) != round($order->cod, 2)) {
            $this->warnings[$line]['line']     = $line;
            $this->warnings[$line]['errors'][] = ['cod_total' => Validator::ERROR_INVALID];
            $row['cod_total_order']          = $codTotalOrder;
        }

        if (!empty($this->warnings[$line])) {
            $row['freight_bill_id'] = $freightBill->id;
            $row['order_id'] = $order->id;
            $this->warnings[$line]['row'] = $row;
        }

        if (empty($this->errors[$line]) && empty($this->warnings[$line])) {
            $this->processedRows[] = array_merge($row, ['order_id' => $order->id, 'freight_bill_id' => $freightBill->id]);
        }
    }
}
