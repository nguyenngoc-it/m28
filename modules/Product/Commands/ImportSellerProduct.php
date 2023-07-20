<?php

namespace Modules\Product\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Product\Validators\ImportedSellerProductValidator;
use Modules\Service;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportSellerProduct
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
        (new FastExcel())->sheet(1)->import($this->file, function ($row) use (&$line) {
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

        $validator = new ImportedSellerProductValidator($row, $this->user);
        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
                'product_code' => Arr::get($row, 'product_code', null),
            ];
            return;
        }
        $row['weight'] = !is_null($row['weight']) ? round($row['weight'] / 1000, 3) : null;
        $row['length'] = !is_null($row['length']) ? round($row['length'] / 1000, 3) : null;
        $row['width']  = !is_null($row['width']) ? round($row['width'] / 1000, 3) : null;
        $row['height'] = !is_null($row['height']) ? round($row['height'] / 1000, 3) : null;

        DB::transaction(function () use ($row, $validator) {
            $newProduct = Product::updateOrCreate(
                [
                    'tenant_id' => $this->user->tenant_id,
                    'code' => $row['product_code'],
                    'merchant_id' => $this->user->merchant->id,
                ],
                [
                    'creator_id' => $this->user->id,
                    'status' => Sku::STATUS_ON_SELL,
                    'name' => $row['product_name'],
                    'weight' => $row['weight'],
                    'height' => $row['height'],
                    'width' => $row['width'],
                    'length' => $row['length'],
                ]
            );
            ProductMerchant::create(['product_id' => $newProduct->id, 'merchant_id' => $this->user->merchant->id]);

            if ($validator->getServicePrice()) {
                $newProduct->productServicePrices()->create([
                    'tenant_id' => $this->user->tenant_id,
                    'service_id' => $validator->getServicePrice()->service->id,
                    'service_price_id' => $validator->getServicePrice()->id
                ]);
            }

            /** @var Service\Models\ServicePrice $servicePriceExport */
            foreach ($validator->getServicePriceExports() as $servicePriceExport) {
                $newProduct->productServicePrices()->create([
                    'tenant_id' => $this->user->tenant_id,
                    'service_id' => $servicePriceExport->service->id,
                    'service_price_id' => $servicePriceExport->id
                ]);
            }

            /** @var Service\Models\ServicePrice $servicePriceImport */
            foreach ($validator->getServicePriceImports() as $servicePriceImport) {
                $newProduct->productServicePrices()->create([
                    'tenant_id' => $this->user->tenant_id,
                    'service_id' => $servicePriceImport->service->id,
                    'service_price_id' => $servicePriceImport->id
                ]);
            }

            Service::product()->createSellerSKU($newProduct, $row, $this->user);
            (new ProductCreated($newProduct->id))->queue();

        });
        $this->processedRows[] = $row['product_code'];
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
            'price',
            'service_shipping',
            'weight',
            'length',
            'width',
            'height',
            'service_importing',
            'service_exporting',
        ];

        $row = array_combine($params, $row);
        foreach (['weight', 'length', 'width', 'height'] as $key) {
            if ($row[$key] == '') {
                $row[$key] = null;
            } else {
                $row[$key] = (float)$row[$key];
            }
        }
        return $row;
    }
}
