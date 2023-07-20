<?php

namespace Modules\Product\Commands;

use Gobiz\Transformer\TransformerService;
use Illuminate\Support\Arr;
use Modules\Product\Models\SkuPrice;
use Modules\Product\Validators\ImportedPriceValidator;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportPrice
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
     * ImportPrice constructor.
     * @param User $user
     * @param $file
     */
    public function __construct(User $user, $file)
    {
        $this->user = $user;
        $this->tenant = $user->tenant;
        $this->file   = $file;
    }

    /**
     * @return array
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    public function handle()
    {
        $line = 1;
        (new FastExcel())->import($this->file, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

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

        $rowInput = array_filter($row, function ($value) {
            return $value != '';
        });
        $validator = new ImportedPriceValidator($this->user, $rowInput, $this->insertedSkuKeys);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
            ];
            return;
        }

        SkuPrice::updateOrCreate(
            [
                'merchant_id' => $validator->merchant()->id,
                'sku_id' => $validator->sku()->id,
            ], [
                'cost_price' => Arr::get($rowInput, 'cost_price', null),
                'wholesale_price' => Arr::get($rowInput, 'wholesale_price', null),
                'retail_price' => $rowInput['retail_price'],
            ]);

        $validator->sku()->update($rowInput);

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
            'merchant_code',
            'cost_price',
            'wholesale_price',
            'retail_price',
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
