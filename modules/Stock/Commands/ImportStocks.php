<?php

namespace Modules\Stock\Commands;

use Exception;
use Gobiz\Transformer\TransformerService;
use Modules\ImportHistory\Models\ImportHistory;
use Modules\ImportHistory\Models\ImportHistoryItem;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Stock\Validators\ImportedStockValidator;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportStocks
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
    protected $insertedStockKeys = [];

    protected $importCodes = [];

    /**
     * ImportStocks constructor
     *
     * @param Tenant $tenant
     * @param string $filePath
     * @param User $user
     */
    public function __construct(Tenant $tenant, $filePath, User $user)
    {
        $this->tenant   = $tenant;
        $this->filePath = $filePath;
        $this->user     = $user;
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

        if (!empty($this->importCodes)) {
            foreach ($this->importCodes as $importCode => $items) {
                $importHistory = ImportHistory::create([
                    'creator_id' => $this->user->id,
                    'tenant_id' => $this->tenant->id,
                    'code' => $importCode
                ]);
                $stock         = 0;
                foreach ($items as $item) {
                    ImportHistoryItem::create(array_merge($item, [
                        'tenant_id' => $this->tenant->id,
                        'import_history_id' => $importHistory->id
                    ]));
                    $stock += $item['stock'];
                }
                $importHistory->stock = $stock;
                $importHistory->save();
            }
        }
        @unlink($this->filePath);

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
            return !empty($value);
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

        $validator = new ImportedStockValidator($this->user, $row, $this->insertedStockKeys);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'errors' => TransformerService::transform($validator),
            ];
            return;
        }

        $sku           = $validator->getSku();
        $warehouse     = $validator->getWarehouse();
        $warehouseArea = $validator->getWarehouseArea() ?: $warehouse->getDefaultArea();

        $stock = Service::stock()->make($sku, $warehouseArea);
        $stock->do(Stock::ACTION_IMPORT, (int)$row['stock'], $this->user)
            ->with($row)
            ->run();

        $this->insertedStockKeys[] = $validator->getStockKey();

        $import_code = trim($row['import_code']);
        if (!isset($this->importCodes[$import_code])) {
            $this->importCodes[$import_code] = [];
        }
        $this->importCodes[$import_code][] = array_merge($row, [
            'sku_id' => $sku->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_area_id' => $warehouseArea->id,
            'freight_bill' => $row['shipping_code']
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'import_code',
            'sku_code',
            'warehouse_code',
            'warehouse_area_code',
            'stock',
            'shipping_code',
            'package_code',
            'note'
        ];

        if (isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if (count($values) != count($params)) {
            return [];
        }

        return array_combine($params, $values);
    }
}
