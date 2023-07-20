<?php

namespace Modules\OrderPacking\Commands;

use App\Base\Validator;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\OrderPacking\Validators\OrderPackingScanValidator;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportBarcode
{
    /** @var Warehouse */
    protected $warehouse;
    /** @var User $user */
    protected $user;

    /**
     * @var string
     */
    protected $barcodeType = '';

    /** @var UploadedFile */
    protected $file;
    /** @var array $processedRows */
    protected $processedRows = [];
    /** @var array $errors */
    protected $errors = [];
    /** @var array $data */
    protected $data = [];

    /**
     * ImportBarcode constructor.
     * @param UploadedFile $file
     * @param Warehouse $warehouse
     * @param $barcodeType
     * @param User $user
     */
    public function __construct(UploadedFile $file, Warehouse $warehouse, $barcodeType,  User $user)
    {
        $this->warehouse   = $warehouse;
        $this->user        = $user;
        $this->file        = $file;
        $this->barcodeType = $barcodeType;
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

        if (empty($this->errors) && empty($this->processedRows)) {
            $this->errors[] = ['file' => 'empty'];
        }

        return [
            'errors' => $this->errors,
            'data'   => $this->data
        ];
    }

    /**
     * @param array $row
     * @param int $line
     */
    protected function processRow(array $row, $line)
    {
        $row = array_map(function ($value) {
            return trim($value);
        }, $row);

        $rowData = array_filter($row, function ($value) {
            return $value != '';
        });

        if (!count($rowData)) {
            return;
        }

        $row = $this->makeRow($row);
        if (!$row) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'INVALID',
            ];
            return;
        }
        $barcode = Arr::get($row, 'barcode', null);

        if(in_array($barcode, $this->processedRows)) {
            $this->errors[] = [
                'line' => $line,
                'barcode' => $barcode,
                'errors' => ['barcode' => [Validator::ERROR_DUPLICATED => []]],
            ];
            return;
        }

        $inputs  = [
            'warehouse_id' => $this->warehouse->id,
            'barcode_type' => $this->barcodeType,
            'barcode' => $barcode,
            'warehouse' => $this->warehouse
        ];
        $validator = new OrderPackingScanValidator($this->user->tenant, $inputs);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
                'barcode' => $barcode,
            ];
            return;
        }

        $orderPacking      = $validator->getOrderPacking();
        $orderPackingItems = $orderPacking->orderPackingItems->load(['sku']);
        $this->data[] = [
            'freight_bill' => $freightBill = $validator->getFreightBill(),
            'order' => $validator->getOrder() ?: $freightBill->order,
            'order_packing' => $orderPacking,
            'order_packing_items' => $orderPackingItems,
            'order_packing_services' => $orderPacking->orderPackingServices
        ];

        $this->processedRows[] = $barcode;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'barcode',
        ];

        if (isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if (count($values) != count($params)) {
            return false;
        }

        return  array_combine($params, $values);
    }
}
