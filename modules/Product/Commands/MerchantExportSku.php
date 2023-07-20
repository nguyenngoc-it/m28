<?php

namespace Modules\Product\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Gobiz\Database\DBHelper;
use Illuminate\Support\Arr;
use Jenssegers\Mongodb\Eloquent\Builder;
use Modules\Order\Models\Order;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\User\Models\User;
use Modules\WarehouseStock\Models\WarehouseStock;
use Rap2hpoutre\FastExcel\FastExcel;
use Generator;

class MerchantExportSku
{
    /** @var User $user */
    protected $user;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var array
     */
    protected $filter;

    /**
     * ExportOrder constructor.
     * @param array $filter
     * @param User $user
     */
    public function __construct(array $filter, User $user)
    {
        $this->user   = $user;
        $this->filter = $filter;
        $productIds   = ProductMerchant::query()->where('merchant_id', $this->user->merchant->id)
            ->pluck('product_id')->toArray();

        $this->filter['product_id'] = $productIds;
        $this->builder              = Service::product()->skuQuery($this->filter)->with([
            'stocks',
            'warehouseStocks',
        ]);
    }

    /**
     * @return Generator
     */
    public function makeGenerator()
    {
        /**
         * @var Order $order
         */
        $results     = DBHelper::chunkByIdGenerator($this->builder, 100, 'skus.id', 'id');
        $warehouseId = Arr::get($this->filter, 'warehouse_id');
        if ($warehouseId && !is_array($warehouseId)) {
            $warehouseId = [$warehouseId];
        }
        foreach ($results as $skus) {
            /** @var Sku $sku */
            foreach ($skus as $sku) {
                yield $this->makeRow($sku, $warehouseId);
            }
        }
    }

    /**
     * @param Sku $sku
     * @param $warehouseId
     * @return array|void
     */
    protected function makeRow(Sku $sku, $warehouseId)
    {
        $warehouseStocks = $warehouseId ? $sku->warehouseStocks->whereIn('warehouse_id', $warehouseId) : $sku->warehouseStocks;
        if (!$warehouseStocks->count()) {
            if (empty($this->filter['lack_of_export_goods'])) {
                return [
                    trans('sku_code') => $sku->code,
                    trans('sku_name') => $sku->name,
                    trans('warehouse') => trans('not_in_stock_yet'),
                    trans('inventory') => '',
                    trans('waiting_import') => '',
                    trans('waiting_export') => '',
                    trans('available_inventory') => '',
                    trans('quantity_missing') => '',
                    trans('storage_amount') => '',
                ];
            }
        } else {
            /** @var WarehouseStock $warehouseStock */
            foreach ($warehouseStocks as $warehouseStock) {
                $quantityMissing = $warehouseStock->packing_quantity - $warehouseStock->purchasing_quantity - $warehouseStock->real_quantity;
                if (
                    !empty($this->filter['lack_of_export_goods']) &&
                    $quantityMissing < 0
                ) {
                    continue;
                }

                $warehouse = $warehouseStock->warehouse;
                return [
                    trans('sku_code') => $sku->code,
                    trans('sku_name') => $sku->name,
                    trans('warehouse') => $warehouse->name,
                    trans('inventory') => $warehouseStock->real_quantity,
                    trans('waiting_import') => $warehouseStock->purchasing_quantity,
                    trans('waiting_export') => $warehouseStock->packing_quantity,
                    trans('available_inventory') => $warehouseStock->real_quantity - $warehouseStock->packing_quantity,
                    trans('quantity_missing') => max($quantityMissing, 0),
                    trans('storage_amount') => round($sku->stocks->where('warehouse_id', $warehouse->id)->sum('total_storage_fee'), 2)
                ];
            }
        }
    }


    /**
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function handle()
    {
        return (new FastExcel($this->makeGenerator()))->export('seller-skus.xlsx');
    }

}
