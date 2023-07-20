<?php

namespace Modules\Store\Commands;

use App\Base\Validator;
use Exception;
use Gobiz\Transformer\TransformerService;
use Modules\Marketplace\Services\Marketplace;
use Modules\Product\Models\Sku;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportStore
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
     * ImportStore constructor.
     * @param $filePath
     * @param User $user
     */
    public function __construct($filePath, User $user)
    {
        $this->tenant = $user->tenant;
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
                'errors' => Validator::ERROR_INVALID,
            ];
            return;
        }

        $insertedSkuKey = $row['shop_id'].'_'.$row['marketplace_sku_code'].$row['m28_sku_code'];
        if(in_array($insertedSkuKey, $this->insertedSkuKeys)) {
            return;
        }


        $required = [];
        foreach (['shop_id', 'marketplace_sku_code', 'm28_sku_code'] as $key) {
            if(empty($row[$key])) {
                $required[] = $key;
            }
        }
        if(!empty($required)) {
            $this->errors[] = [
                'line' => $line,
                'errors' => Validator::ERROR_REQUIRED,
                'keys' => $required
            ];
            return;
        }

        $store = Store::query()->firstWhere([
            'tenant_id' => $this->tenant->id,
            'marketplace_code' => Marketplace::CODE_SHOPEE,
            'marketplace_store_id' => $row['shop_id'],
            'status' => Store::STATUS_ACTIVE
        ]);
        if(!$store instanceof Store) {
            $this->errors[] = [
                'line' => $line,
                'errors' => 'shop_not_connect',
                'shop_id' => $row['shop_id']
            ];
            return;
        }

        $sku = Sku::query()->firstWhere([
            'tenant_id' => $this->tenant->id,
            'code' => $row['m28_sku_code']
        ]);

        if(!$sku instanceof Sku) {
            $this->errors[] = [
                'line' => $line,
                'errors' => Validator::ERROR_EXISTS,
                'code' => $row['m28_sku_code']
            ];
            return;
        }

        $storeSku = StoreSku::query()->firstWhere([
            'tenant_id' => $this->tenant->id,
            'store_id' => $store->id,
            'code' => $row['marketplace_sku_code']
        ]);
        if($storeSku instanceof StoreSku) {
            $this->errors[] = [
                'line' => $line,
                'errors' => Validator::ERROR_ALREADY_EXIST,
                'code' => $row['marketplace_sku_code']
            ];
            return;
        }

        StoreSku::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $store->id,
            'sku_id' => $sku->id,
            'code' => $row['marketplace_sku_code'],
            'marketplace_code' => $store->marketplace_code,
            'marketplace_store_id' => $store->marketplace_store_id,
        ]);

        $this->insertedSkuKeys[] = $insertedSkuKey;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'shop_id',
            'marketplace_sku_code',
            'm28_sku_code',
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