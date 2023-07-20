<?php

namespace Modules\Document\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\Document\Models\Document;
use Modules\Document\Validators\ImportedSkuInventoryValidator;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\User\Models\User;
use Modules\Warehouse\Models\WarehouseArea;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportSkuInventory
{
    /** @var Document */
    protected $documentSkuInventory;
    /** @var User $user */
    protected $user;

    /** @var Merchant | null */
    protected $merchant;

    /** @var UploadedFile */
    protected $file;
    /** @var array $processedRows */
    protected $processedRows = [];
    /** @var array $errors */
    protected $errors = [];

    /**
     * ImportSkuInventory constructor.
     * @param UploadedFile $file
     * @param Document $documentSkuInventory
     * @param User $user
     * @param null $merchant
     */
    public function __construct(UploadedFile $file, Document $documentSkuInventory, User $user, $merchant = null)
    {
        $this->documentSkuInventory = $documentSkuInventory;
        $this->user                 = $user;
        $this->file                 = $file;
        $this->merchant             = $merchant;
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

        return $this->errors;
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

        $validator = new ImportedSkuInventoryValidator($this->user, $row, $this->merchant);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
                'sku_code' => Arr::get($row, 'sku_code', null),
            ];
            return;
        }

        $warehouseArea = $this->documentSkuInventory->warehouse->areas()->where('code', $row['warehouse_area_code'])->first();
        if (!$warehouseArea instanceof WarehouseArea) {
            $this->errors[] = [
                'line' => $line,
                'errors' => ['warehouse_area_code' => [ImportedSkuInventoryValidator::ERROR_INVALID => []]],
                'warehouse_area_code' => Arr::get($row, 'warehouse_area_code', null),
            ];
            return;
        }

        if ($sku = $validator->getSku()) {
            Service::documentSkuInventory()->scanSku($this->documentSkuInventory, $warehouseArea, $sku, $this->user, $validator->getQuanityChecked());
        }

        $this->processedRows[] = $row['sku_code'];
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'warehouse_area_code',
            'product',
            'sku_code',
            'sku_name',
            'quantity_stock',
            'quantity_checked',
        ];

        $row = array_combine($params, $row);
        return [
            'warehouse_area_code' => $row['warehouse_area_code'],
            'sku_code' => $row['sku_code'],
            'quantity_checked' => $row['quantity_checked'] == '' ? null : (int)$row['quantity_checked']
        ];
    }
}
