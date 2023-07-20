<?php

namespace Modules\Product\Commands;

use Exception;
use Gobiz\Transformer\TransformerService;
use Modules\Product\Validators\ImportedSKUValidator;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportSKUs
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $errors;

    /**
     * @var array
     */
    protected $insertedSkuKeys = [];

    /**
     * ImportSKUs constructor
     *
     * @param Tenant $tenant
     * @param string $filePath
     * @param User $user
     */
    public function __construct(Tenant $tenant, $filePath, User $user)
    {
        $this->tenant = $tenant;
        $this->filePath = $filePath;
        $this->user = $user;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function handle()
    {
        $line = 1;
        (new FastExcel())->import($this->filePath, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

        @unlink($this->filePath);

        return $this->errors;
    }

    /**
     * @param array $row
     * @param int $line
     */
    protected function processRow(array $row, $line)
    {
        $row = array_map(function($value){
            return trim($value);
        }, $row);

        $rowData = array_filter($row, function($value){
            return !empty($value);
        });
        if(!count($rowData)) {
            return;
        }

        $row = $this->makeRow($row);
        if(!$row) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'INVALID',
            ];
            return;
        }

        $validator = new ImportedSKUValidator($this->tenant, $row, $this->insertedSkuKeys);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
            ];
            return;
        }

        (new ImportSKU(array_merge($row, [
            'tenant' => $this->tenant,
            'unit' => $validator->getUnit(),
            'category' => $validator->getCategory(),
            'creator' => $this->user
        ])))->handle();

        $this->insertedSkuKeys[] = $validator->getSkuKey();
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'sku_code',
            'sku_name',
            'barcode',
            'unit_code',
            'category_code',
            'color',
            'size',
            'type',
            'option_1',
            'option_1_value',
            'option_2',
            'option_2_value',
            'option_3',
            'option_3_value',
            'product_description'
        ];

        if(isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if(count($values) != count($params)) {
            return false;
        }

        return array_combine($params, $values);
    }
}