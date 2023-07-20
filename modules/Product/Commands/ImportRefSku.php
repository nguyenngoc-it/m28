<?php

namespace Modules\Product\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\Product\Models\Sku;
use Modules\Product\Services\ProductEvent;
use Modules\Product\Services\SkuEvent;
use Modules\Product\Validators\ImportedRefSkuValidator;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportRefSku
{
    /**
     * @var User
     */
    protected $user;
    /** @var UploadedFile */
    protected $file;
    /** @var array $processedRows */
    protected $processedRows = [];
    /** @var array $errors */
    protected $errors = [];


    public function __construct(UploadedFile $file, User $user)
    {
        $this->user = $user;
        $this->file = $file;
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

        $validator = new ImportedRefSkuValidator($this->user, $row);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
                'sku_code' => Arr::get($row, 'sku_code', null),
            ];
            return;
        }

        $sku = $validator->getSku();
        if ($sku instanceof Sku) {
            $sku->logActivity(SkuEvent::SKU_UPDATE_REF, $this->user, [
                'from' => $sku->ref,
                'to' => $row['ref'],
            ]);

            $sku->product->logActivity(ProductEvent::SKU_UPDATE_REF, $this->user, [
                'from' => $sku->ref,
                'to' => $row['ref'],
                'sku' => $sku->only(['id', 'code', 'name', 'ref'])
            ]);

            $sku->update(['ref' => $row['ref']]);
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
            'product_code',
            'product_name',
            'product_description',
            'sku_code',
            'sku_name',
            'ref'
        ];

        $row = array_combine($params, $row);
        if (empty($row['ref'])) {
            $row['ref'] = null;
        }
        return $row;
    }
}
