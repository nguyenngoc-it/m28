<?php

namespace Modules\Product\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Transformer\TransformerService;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Product\Validators\ImportFobizSkuCodeValidator;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportFobizSKUCode
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $insertedSkuKeys = [];

    /**
     * @var Store
     */
    protected $store;


    /**
     * ImportFobizSKUCode constructor.
     * @param User $user
     * @param $file
     */
    public function __construct(User $user, $file)
    {
        $this->user   = $user;
        $this->tenant = $user->tenant;
        $this->file   = $file;
    }

    /**
     * @return array
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        $this->store = Store::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'marketplace_code' => Marketplace::CODE_FOBIZ,
        ])->first();

        if (!$this->store instanceof Store) {
            $this->errors[] = ['store' => 'invalid'];
            return $this->errors;
        }

        $line = 1;
        (new FastExcel())->import($this->file, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

        if (empty($this->errors) && empty($this->insertedSkuKeys)) {
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

        $rowInput  = array_filter($row, function ($value) {
            return $value != '';
        });
        $validator = new ImportFobizSkuCodeValidator($this->user, $this->store, $rowInput, $this->insertedSkuKeys);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
                'sku_code' => Arr::get($rowInput, 'sku_code', null),
                'fobiz_code' => Arr::get($rowInput, 'fobiz_code', null),
            ];
            return;
        }

        $this->store->storeSkus()->create([
            'tenant_id' => $this->user->tenant_id,
            'marketplace_code' => $this->store->marketplace_code,
            'marketplace_store_id' => $this->store->marketplace_store_id,
            'sku_id' => $validator->getSku()->id,
            'code' => $rowInput['fobiz_code'],
            'sku_id_origin' => $rowInput['fobiz_code']
        ]);

        $this->insertedSkuKeys[] = $rowInput['fobiz_code'];
    }

    /**
     * @param array $row
     * @return array|false
     */
    protected function makeRow(array $row)
    {
        $params = [
            'sku_code',
            'fobiz_code',
        ];

        if (isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if (count($values) != count($params)) {
            return false;
        }

        return array_combine($params, $values);
    }
}
